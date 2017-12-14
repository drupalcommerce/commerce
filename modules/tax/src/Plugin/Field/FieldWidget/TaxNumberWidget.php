<?php

namespace Drupal\commerce_tax\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'commerce_tax_number_widget' widget.
 *
 * @FieldWidget(
 *   id = "commerce_tax_number_widget",
 *   label = @Translation("Tax Number"),
 *   field_types = {
 *     "commerce_tax_number"
 *   }
 * )
 */
class TaxNumberWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'size' => 60,
        'placeholder' => '',
        'show_for_countries' => [],
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['size'] = [
      '#type' => 'number',
      '#title' => t('Size of textfield'),
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#min' => 1,
    ];

    $elements['placeholder'] = [
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];

    $elements['show_for_countries'] = [
      '#type' => 'select',
      '#title' => t('Show for countries'),
      '#description' => t('Shows tax number for selected countries. Countries are checked from address field in profile'),
      '#options' => array_merge(['all' => t('-- All Countries --')], \Drupal::service('address.country_repository')
        ->getList()),
      '#multiple' => TRUE,
      '#default_value' => $this->getSetting('show_for_countries'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('Textfield size: @size', ['@size' => $this->getSetting('size')]);
    if (!empty($this->getSetting('placeholder'))) {
      $summary[] = t('Placeholder: @placeholder', ['@placeholder' => $this->getSetting('placeholder')]);
    }
    if (!empty($this->getSetting('show_for_countries'))) {
      $summary[] = t('Show for countries: @countries', ['@countries' => implode(",", $this->getSetting('show_for_countries'))]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#maxlength' => $this->getFieldSetting('max_length'),
    ];

    // Check if country is applicable for showing
    // @TODO: We may want to determine this another way.
    $country_code = FALSE;
    if (isset($form['address']['widget'][0]['address']['#default_value']['country_code'])) {
      $country_code = $form['address']['widget'][0]['address']['#default_value']['country_code'];
    }
    if (!$this->countryIsApplicable($country_code)) {
      $element['value']['#access'] = FALSE;
    }
    return $element;
  }


  /**
   * Check if country is applicable for validation or showing
   *
   * @param string $country_code
   *
   * @return bool
   */
  protected function countryIsApplicable($country_code) {
    $settings = $this->getSetting('show_for_countries');
    return (in_array($country_code, $settings) || in_array('all', $settings) || empty($settings)) ? TRUE : FALSE;
  }
}
