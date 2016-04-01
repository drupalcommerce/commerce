<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Plugin\Field\FieldWidget\BillingAddressWidget.
 */

namespace Drupal\commerce_order\Plugin\Field\FieldWidget;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\profile\ProfileStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'billing_address_options' widget.
 *
 * @FieldWidget(
 *   id = "billing_address_options",
 *   label = @Translation("Billing Addresses"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class BillingAddressWidget extends OptionsWidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The address entity storage.
   *
   * @var Drupal\profile\ProfileStorage
   */
  protected $addressStorage;

  /**
   * The entity query factory service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Constructs a BillingAddressWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param Drupal\profile\ProfileStorage $address_storage
   *   The profile address storage.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entityQuery
   *   The entity query factory.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ProfileStorage $address_storage, QueryFactory $entity_query) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->addressStorage = $address_storage;
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity.manager')->getStorage('profile'),
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'size' => 60,
      'placeholder' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['size'] = array(
      '#type' => 'number',
      '#title' => t('Size of textfield'),
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#min' => 1,
    );
    $elements['placeholder'] = array(
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('Textfield size: !size', array('!size' => $this->getSetting('size')));
    if (!empty($this->getSetting('placeholder'))) {
      $summary[] = t('Placeholder: @placeholder', array('@placeholder' => $this->getSetting('placeholder')));
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element = parent::formElement($items, $delta, $element, $form, $form_state);;

    $options = $this->getOptions($items->getEntity());
    $selected = $this->getSelectedOptions($items);
    $addresses = $this->getBillingAddresses($items);

    // If required and there is one single option, preselect it.
    if ($this->required && count($addresses) == 1) {
      reset($addresses);
      $selected = array(key($addresses));
    }

    if (count($addresses) > 1) {
      $element += array(
        '#type' => 'radios',
        '#default_value' => $selected ? reset($selected) : NULL,
        '#options' => $addresses,
      );
    } else {
      $element += array(
        '#type' => 'checkboxes',
        '#default_value' => $selected,
        '#options' => $addresses,
      );
    }

    return $element;
  }

  private function getBillingAddresses($items) {

    $addressIds = $this->entityQuery->get('profile')
      ->condition('type', 'billing')
      ->condition('uid', $items->getEntity()->getOwnerId())
      ->execute();
    $addresses = $this->addressStorage->loadMultiple($addressIds);

    $arrAddresses = array();

    foreach ($addresses as $address) {

      $arrAddress = $address->toArray();

      $details = $arrAddress['is_default'][0]['value'] ? '(default address)<br/>' : NULL;
      $details .= $arrAddress['address'][0]['recipient'];
      $details .= !empty($arrAddress['address'][0]['organization']) ? '<br/>' . $arrAddress['address'][0]['organization'] : NULL;
      $details .= '<br/>' . $arrAddress['address'][0]['address_line1'];
      $details .= !empty($arrAddress['address'][0]['address_line2']) ? '<br/>' . $arrAddress['address'][0]['address_line2'] : NULL;
      $details .= '<br/>' . $arrAddress['address'][0]['locality'] . ', ' 
        . substr($arrAddress['address'][0]['administrative_area'], 3) . ' '
        . $arrAddress['address'][0]['postal_code'];

      $arrAddresses[$address->id()] = $details;
    }

    return $arrAddresses;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmptyLabel() {
    return NULL;
  }
}
