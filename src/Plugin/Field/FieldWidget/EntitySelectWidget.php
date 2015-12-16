<?php

/**
 * @file
 * Contains \Drupal\commerce\Plugin\Field\FieldWidget\EntitySelectWidget.
 */

namespace Drupal\commerce\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_select' widget.
 *
 * @FieldWidget(
 *   id = "entity_select",
 *   label = @Translation("Entity select"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class EntitySelectWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'autocomplete_threshold' => 7,
      'autocomplete_size' => 60,
      'autocomplete_placeholder' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $formState) {
    $element = [];
    $element['autocomplete_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Autocomplete threshold'),
      '#description' => $this->t('Number of available entities after which the autocomplete is used.'),
      '#default_value' => $this->getSetting('autocomplete_threshold'),
      '#min' => 2,
      '#required' => TRUE,
    ];
   $element['autocomplete_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Autocomplete size'),
      '#description' => $this->t('Size of the input field in characters.'),
      '#default_value' => $this->getSetting('autocomplete_size'),
      '#min' => 1,
      '#required' => TRUE,
    ];
    $element['autocomplete_placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Autocomplete placeholder'),
      '#default_value' => $this->getSetting('autocomplete_placeholder'),
      '#description' => $this->t('Text that will be shown inside the autocomplete field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Autocomplete threshold: @threshold entities.', ['@threshold' => $this->getSetting('autocomplete_threshold')]);
    $summary[] = $this->t('Autocomplete size: @size characters', ['@size' => $this->getSetting('autocomplete_size')]);
    $placeholder = $this->getSetting('autocomplete_placeholder');
    if (!empty($placeholder)) {
      $summary[] = $this->t('Autocomplete placeholder: @placeholder', ['@placeholder' => $placeholder]);
    }
    else {
      $summary[] = $this->t('No autocomplete placeholder');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $settings = $this->getSettings();
    $multiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();
    $values = $items->getValue();
    if ($multiple) {
      $default_value = array_column($values, 'target_id');
    }
    else {
      $default_value = !empty($values) ? $values[0]['target_id'] : NULL;
    }

    $element += [
      '#type' => 'entity_select',
      '#target_type' => $this->getFieldSetting('target_type'),
      '#multiple' => $multiple,
      '#default_value' => $default_value,
      '#autocomplete_threshold' => $settings['autocomplete_threshold'],
      '#autocomplete_size' => $settings['autocomplete_size'],
      '#autocomplete_placeholder' => $settings['autocomplete_placeholder'],
      '#required' => $this->fieldDefinition->isRequired(),
    ];

    return ['target_id' => $element];
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    return $values['target_id'];
  }

}
