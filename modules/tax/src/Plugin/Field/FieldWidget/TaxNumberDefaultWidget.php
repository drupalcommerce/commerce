<?php

namespace Drupal\commerce_tax\Plugin\Field\FieldWidget;

use Drupal\address\Element\Address;
use Drupal\address\Element\Country;
use Drupal\commerce_tax\TaxNumberTypeManagerInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'commerce_tax_number_default' widget.
 *
 * @FieldWidget(
 *   id = "commerce_tax_number_default",
 *   label = @Translation("Tax number"),
 *   field_types = {
 *     "commerce_tax_number"
 *   }
 * )
 */
class TaxNumberDefaultWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The tax number type manager.
   *
   * @var \Drupal\commerce_tax\TaxNumberTypeManagerInterface
   */
  protected $taxNumberTypeManager;

  /**
   * Constructs a new TaxNumberDefaultWidget object.
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
   * @param \Drupal\commerce_tax\TaxNumberTypeManagerInterface $tax_number_type_manager
   *   The tax number type manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, TaxNumberTypeManagerInterface $tax_number_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->taxNumberTypeManager = $tax_number_type_manager;
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
      $container->get('plugin.manager.commerce_tax_number_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    if ($this->isDefaultValueWidget($form_state)) {
      return [];
    }
    $this->prepareForm($form);
    /** @var \Drupal\commerce_tax\Plugin\Field\FieldType\TaxNumberItemInterface $item */
    $item = $items[$delta];
    $selected_country = $this->getSelectedCountry($form, $form_state);
    $allowed_countries = $item->getAllowedCountries();
    if ($selected_country && !in_array($selected_country, $allowed_countries)) {
      // Tax numbers are not being collected for the selected country.
      $element['#access'] = FALSE;
      $element['value'] = [
        '#type' => 'value',
        '#value' => '',
      ];
      return $element;
    }

    $allowed_types = $item->getAllowedTypes();
    if (count($allowed_types) == 1) {
      $type = reset($allowed_types);
    }
    elseif ($selected_country) {
      // Auto-detect the tax number type based on the selected country.
      $type = $this->taxNumberTypeManager->getPluginId($selected_country);
    }
    else {
      // There are multiple allowed types, but auto-detection is not possible.
      // Fall back to the "Other" plugin, and perform no validation.
      // @todo Allow the user to select the type from a dropdown instead.
      $type = 'other';
    }

    $element['original'] = [
      '#type' => 'value',
      '#value' => $item->getValue() + [
        'type' => NULL,
        'value' => NULL,
        'verification_state' => NULL,
        'verification_timestamp' => NULL,
        'verification_result' => NULL,
      ],
    ];
    $element['type'] = [
      '#type' => 'value',
      '#value' => $type,
    ];
    $element['value'] = [
      '#type' => 'textfield',
      '#title' => $this->fieldDefinition->getLabel(),
      '#description' => $this->fieldDefinition->getDescription(),
      '#required' => $this->fieldDefinition->isRequired(),
      '#default_value' => $item->value,
      '#size' => 20,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $delta => $value) {
      if (empty($value['value'])) {
        continue;
      }
      /** @var \Drupal\commerce_tax\Plugin\Commerce\TaxNumberType\TaxNumberTypeInterface $type_plugin */
      $type_plugin = $this->taxNumberTypeManager->createInstance($value['type']);
      $value['value'] = $type_plugin->canonicalize($value['value']);
      // Preserve the previous verification if the number hasn't changed.
      $original = $value['original'];
      if ($original['type'] == $value['type'] && $original['value'] == $value['value']) {
        $value['verification_state'] = $original['verification_state'];
        $value['verification_timestamp'] = $original['verification_timestamp'];
        $value['verification_result'] = $original['verification_result'];
      }
      unset($value['original']);
      $values[$delta] = $value;
    }
    return $values;
  }

  /**
   * Prepares the given entity form.
   *
   * Ensures that the address widget refreshes the entire entity form, to
   * allow the tax_number widget to hide itself based on the selected country.
   *
   * @param array $form
   *   The entity form.
   *
   * @return array
   *   The prepared form.
   */
  protected function prepareForm(array &$form) {
    if (empty($form['address']['widget'][0]['address']['#required'])) {
      // The address field is missing, optional, or using a non-standard widget.
      return $form;
    }

    $wrapper_id = Html::getUniqueId(implode('-', $form['#parents']) . '-ajax-form');
    $form += [
      '#wrapper_id' => $wrapper_id,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
    ];
    $form['address']['widget'][0]['address']['#form_wrapper'] = $form['#wrapper_id'];
    $form['address']['widget'][0]['address']['#process'] = [
      // Keep the default #process functions defined in Address::getInfo().
      [Address::class, 'processAddress'],
      [Address::class, 'processGroup'],
      // Add our own #process.
      [get_class($this), 'replaceAjaxCallback'],
    ];

    return $form;
  }

  /**
   * Replaces the country_code #ajax callback in an Address widget.
   *
   * Used as a #process callback because the country_code is a part of the
   * Address form element, added on #process.
   *
   * @param array $element
   *   The form element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The form element.
   */
  public static function replaceAjaxCallback(array &$element, FormStateInterface $form_state, array &$complete_form) {
    if (isset($element['country_code']['#ajax'])) {
      $element['country_code']['#ajax'] = [
        'callback' => [get_called_class(), 'ajaxRefresh'],
        'wrapper' => $element['#form_wrapper'],
      ];
    }
    return $element;
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    // Find the entity form based on the expected address widget structure
    // (e.g. $form['address']['widget'][0]['address']['country_code']).
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#array_parents'], 0, -6);

    return NestedArray::getValue($form, $parents);
  }

  /**
   * Gets the selected country from the parent entity's address field.
   *
   * @param array $form
   *   The entity form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return string|null
   *   The country code, or NULL if not found.
   */
  protected function getSelectedCountry(array $form, FormStateInterface $form_state) {
    if (empty($form['address']['widget'][0]['address']['#required'])) {
      // The address field is missing, optional, or using a non-standard widget.
      return NULL;
    }

    $parents = array_merge($form['#parents'], ['address', 0, 'address', 'country_code']);
    $selected_country = NestedArray::getValue($form_state->getUserInput(), $parents);
    if (!$selected_country) {
      // The form hasn't been submitted yet, use the default value.
      $address_element = $form['address']['widget'][0]['address'];
      $address_element += ['#available_countries' => []];
      if (!empty($address_element['#default_value']['country_code'])) {
        $selected_country = $address_element['#default_value']['country_code'];
      }
      else {
        // The Address::valueCallback() logic hasn't fired yet, simulate it.
        $selected_country = Country::getDefaultCountry($address_element['#available_countries']);
      }
    }

    return $selected_country;
  }

}
