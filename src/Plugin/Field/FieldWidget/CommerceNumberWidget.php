<?php

namespace Drupal\commerce\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\NumberWidget;

/**
 * Plugin implementation of the 'commerce_number' widget.
 *
 * @FieldWidget(
 *   id = "commerce_number",
 *   label = @Translation("Commerce number field"),
 *   field_types = {
 *     "integer",
 *     "decimal",
 *     "float"
 *   }
 * )
 */
class CommerceNumberWidget extends NumberWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'placeholder' => '',
      'step' => '1',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $field_settings = $this->getFieldSettings();

    $step = '1';
    switch ($this->fieldDefinition->getType()) {
      case 'decimal':
        $step = (string) pow(0.1, $field_settings['scale']);
        break;

      case 'float':
        $step = 'any';
        break;
    }

    $element['step'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => $step,
      '#title' => t('Step'),
      '#default_value' => $this->getSetting('step'),
      '#description' => t('The minimum allowed amount to increment or decrement the field value with.'),
      '#required' => TRUE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = t('Step: @step', ['@step' => $this->getSetting('step')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    if (!empty($this->getSetting('step'))) {
      $element['value']['#step'] = $this->getSetting('step');
    }

    return $element;
  }

}
