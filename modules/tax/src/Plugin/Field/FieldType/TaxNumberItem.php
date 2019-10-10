<?php

namespace Drupal\commerce_tax\Plugin\Field\FieldType;

use Drupal\commerce_tax\Plugin\Commerce\TaxNumberType\SupportsVerificationInterface;
use Drupal\commerce_tax\Plugin\Commerce\TaxNumberType\VerificationResult;
use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;

/**
 * Plugin implementation of the 'commerce_tax_number' field type.
 *
 * @FieldType(
 *   id = "commerce_tax_number",
 *   label = @Translation("Tax number"),
 *   category = @Translation("Commerce"),
 *   default_formatter = "commerce_tax_number_default",
 *   default_widget = "commerce_tax_number_default",
 *   cardinality = 1,
 * )
 */
class TaxNumberItem extends FieldItemBase implements TaxNumberItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['type'] = DataDefinition::create('string')
      ->setLabel(t('Type'))
      ->setRequired(TRUE);
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Tax number'))
      ->setRequired(TRUE);
    $properties['verification_state'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Verification state'));
    $properties['verification_timestamp'] = DataDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Verification timestamp'));
    $properties['verification_result'] = MapDataDefinition::create()
      ->setLabel(new TranslatableMarkup('Verification result'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'value';
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'type' => [
          'type' => 'varchar_ascii',
          'length' => 255,
        ],
        'value' => [
          'type' => 'varchar',
          'length' => 64,
        ],
        'verification_state' => [
          'type' => 'varchar',
          'length' => 64,
        ],
        'verification_timestamp' => [
          'type' => 'int',
          'size' => 'big',
        ],
        'verification_result' => [
          'type' => 'blob',
          'size' => 'normal',
          'serialize' => TRUE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'countries' => [],
      'verify' => TRUE,
      'allow_unverified' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $country_list = \Drupal::service('address.country_repository')->getList();
    $country_list = [
      (string) $this->t('Regions') => [
        'EU' => $this->t('European Union'),
      ],
      (string) $this->t('Countries') => $country_list,
    ];

    $element['countries'] = [
      '#type' => 'select',
      '#title' => $this->t('Collect tax numbers for the following countries'),
      '#description' => $this->t('If no countries are selected, all countries will be allowed.'),
      '#options' => $country_list,
      '#default_value' => $this->getSetting('countries'),
      '#multiple' => TRUE,
      '#size' => 10,
    ];
    $element['verify'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Verify the tax number via external web services'),
      '#description' => $this->t('Uses an official service (such as VIES) when one exists.'),
      '#default_value' => $this->getSetting('verify'),
    ];
    $element['allow_unverified'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Accept unverified tax numbers if the verification service is temporarily unavailable'),
      '#description' => $this->t('Merchants can verify the tax number after the order has been placed.'),
      '#default_value' => $this->getSetting('allow_unverified'),
      '#states' => [
        'visible' => [
          ':input[name="settings[verify]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    $max_length = 64;
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints[] = $constraint_manager->create('ComplexData', [
      'type' => [
        'AllowedValues' => [
          'choices' => $this->getAllowedTypes(),
          'message' => t('Invalid type specified.'),
        ],
      ],
      'value' => [
        'Length' => [
          'max' => $max_length,
          'maxMessage' => t('%name: may not be longer than @max characters.', [
            '%name' => $this->getFieldDefinition()->getLabel(),
            '@max' => $max_length,
          ]),
        ],
      ],
      'verification_state' => [
        'AllowedValues' => [
          'choices' => [
            VerificationResult::STATE_SUCCESS,
            VerificationResult::STATE_FAILURE,
            VerificationResult::STATE_UNKNOWN,
          ],
          'message' => t('Invalid verification_state specified.'),
        ],
      ],
    ]);
    $constraints[] = $constraint_manager->create('TaxNumber', [
      'verify' => $this->getSetting('verify'),
      'allowUnverified' => $this->getSetting('allow_unverified'),
    ]);

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name, $notify = TRUE) {
    if ($property_name == 'type') {
      // Make sure the number is re-verified after the type is changed.
      $this->writePropertyValue('verification_state', NULL);
      $this->writePropertyValue('verification_timestamp', NULL);
      $this->writePropertyValue('verification_result', NULL);
    }
    parent::onChange($property_name, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return $this->value === NULL || $this->value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $values['type'] = 'other';
    $values['value'] = $random->word(mt_rand(1, 32));
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();

    if ($this->isEmpty() || !empty($this->verification_state)) {
      return;
    }
    // The validator can't modify the field item in order to store the
    // verification result. This is why verification must be run again here.
    // TaxNumberTypeWithVerificationBase uses a memory cache to avoid making
    // multiple API requests for the same data.
    $type_plugin = $this->getTypePlugin();
    if ($type_plugin instanceof SupportsVerificationInterface) {
      $result = $type_plugin->verify($this->value);
      $this->applyVerificationResult($result);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applyVerificationResult(VerificationResult $result) {
    $this->verification_state = $result->getState();
    $this->verification_timestamp = $result->getTimestamp();
    $this->verification_result = $result->getData();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function checkValue($expected_type) {
    if ($this->isEmpty() || $this->type != $expected_type) {
      return FALSE;
    }

    if ($this->getTypePlugin() instanceof SupportsVerificationInterface) {
      $verification_state = $this->verification_state;
      if ($verification_state == VerificationResult::STATE_UNKNOWN) {
        return $this->getSetting('allow_unverified');
      }
      else {
        return $verification_state == VerificationResult::STATE_SUCCESS;
      }
    }
    else {
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAllowedCountries() {
    $countries = array_filter($this->getSetting('countries'));
    if (in_array('EU', $countries)) {
      // Replace the EU country with actual members of the EU, plus IM and MC.
      // Same list as in the european_union_vat tax number plugin.
      $eu_countries = [
        'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI',
        'FR', 'GB', 'GR', 'HR', 'HU', 'IE', 'IM', 'IT', 'LT', 'LU',
        'LV', 'MC', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK',
      ];
      $countries = array_diff($countries, ['EU']);
      $countries = array_merge($countries, $eu_countries);
    }
    if (empty($countries)) {
      // All countries are allowed.
      $country_list = \Drupal::service('address.country_repository')->getList();
      $countries = array_keys($country_list);
    }

    return $countries;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllowedTypes() {
    $tax_number_type_manager = $this->getTaxNumberTypeManager();
    $countries = array_filter($this->getSetting('countries'));
    if ($countries) {
      $types = [];
      foreach ($countries as $country_code) {
        $types[] = $tax_number_type_manager->getPluginId($country_code);
      }
      $types = array_unique($types);
    }
    else {
      // All types are allowed.
      $types = array_keys($tax_number_type_manager->getDefinitions());
    }
    // Ensure a consistent ordering of plugin IDs.
    sort($types);

    return $types;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypePlugin() {
    if ($this->type) {
      $tax_number_type_manager = $this->getTaxNumberTypeManager();
      return $tax_number_type_manager->createInstance($this->type);
    }
  }

  /**
   * Gets the tax number type plugin manager.
   *
   * @return \Drupal\commerce_tax\TaxNumberTypeManagerInterface
   *   The tax number type plugin manager.
   */
  protected function getTaxNumberTypeManager() {
    return \Drupal::service('plugin.manager.commerce_tax_number_type');
  }

}
