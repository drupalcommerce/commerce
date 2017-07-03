<?php

namespace Drupal\commerce\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
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
    return array(
      'placeholder' => '',
      'min' => '',
      'max' => '',
      'default_value' => '',
      'step' => '',
      'prefix' => '',
      'suffix' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $mode_settings = $this->getSettings();
    $field_settings = $this->getFieldSettings();
    $default_value = array_column($this->fieldDefinition->getDefaultValueLiteral(), 'value');
    $field_settings['placeholder'] = isset($field_settings['placeholder']) ? $field_settings['placeholder'] : '';
    $field_settings['default_value'] = isset($default_value[0]) ? $default_value[0] : '';
    $field_settings['step'] = isset($field_settings['step']) ? $field_settings['step'] : '';
    array_walk($field_settings, function (&$value) {
      if (empty($value)) {
        $value = t('None');
      }
    });
    $scale = empty($field_settings['scale']) ? 0 : $field_settings['scale'];
    $step = '1';
    $notes = '';
    $step_description = t('The minimum allowed amount to increment or decrement the field value with.');

    // Set a minimal valid step to set settings for the corresponding field type.
    switch ($this->fieldDefinition->getType()) {
      case 'decimal':
        $step = (string) pow(0.1, $scale);
        $n = $nn = 'N';
        $format = ['"' . $n . '"'];
        while ($field_settings['scale']--) {
          array_push($format, '"' . $n . '.' . $nn . '"');
          $nn = "$n$nn";
        }
        $notes = t('Restricts the number of digits after decimal sign to the given step format. For this field instance format patterns are the following: @format. Note that omitting the decimal sign in this setting restricts input on the field to integer values despite the actual field type is decimal.', ['@format' => implode(', ', $format)]);
        break;

      case 'float':
        $step = 'any';
        $notes = t('Note that built in step is integer "1" but input on the field could be done in any float or integer format: "N", "N.N", "N.NN", "N.NNN", "N.NNNN", etc..');
        break;
    }

    $default_step = !empty($mode_settings['step']) ? $mode_settings['step'] : ($field_settings['step'] == t('None') ? $step : $field_settings['step']);

    $element['placeholder'] = array(
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $mode_settings['placeholder'],
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format. Leave blank for default = @default.', ['@default' => $field_settings['placeholder']]),
      '#placeholder' => $field_settings['placeholder'],
    );
    $element['min'] = array(
      '#type' => 'number',
      '#title' => t('Minimum'),
      '#step' => $step,
      '#default_value' => (string) $mode_settings['min'],
      '#description' => t('The minimum value that should be allowed in this field. Leave blank for default = @default.', ['@default' => $field_settings['min']]),
      '#placeholder' => $field_settings['min'],
    );
    $element['max'] = array(
      '#type' => 'number',
      '#step' => $step,
      '#title' => t('Maximum'),
      '#default_value' => (string) $mode_settings['max'],
      '#description' => t('The maximum value that should be allowed in this field. Leave blank for default = @default.', ['@default' => $field_settings['max']]),
      '#placeholder' => $field_settings['max'],
    );
    $element['default_value'] = array(
      '#type' => 'number',
      '#title' => t('Default value'),
      '#step' => $step,
      '#default_value' => (string) $mode_settings['default_value'],
      '#description' => t('The default value for this field. Leave blank for default = @default.', ['@default' => $field_settings['default_value']]),
      '#placeholder' => $field_settings['default_value'],
    );
    $element['step'] = array(
      '#type' => 'number',
      '#min' => is_numeric($step) && $step > 0 ? $step : '0',
      '#step' => $step,
      '#title' => t('Step'),
      '#default_value' => $default_step == 'any' ? '1' : (string) $default_step,
      '#description' => implode(' ', [$step_description, $notes]),
      '#placeholder' => t('Insert valid step.'),
      '#required' => TRUE,
    );
    $element['prefix'] = array(
      '#type' => 'textfield',
      '#title' => t('Prefix'),
      '#default_value' => $mode_settings['prefix'],
      '#description' => t('Define a string that should be prefixed to the value, like "$ " or "&euro; ". Leave blank for none. Separate singular and plural values with a pipe ("pound|pounds"). Leave blank for default = @default.', ['@default' => $field_settings['prefix']]),
      '#placeholder' => $field_settings['prefix'],
    );
    $element['suffix'] = array(
      '#type' => 'textfield',
      '#title' => t('Suffix'),
      '#default_value' => $mode_settings['suffix'],
      '#description' => t('Define a string that should be suffixed to the value, like " m", " kb/s". Separate singular and plural values with a pipe ("pound|pounds"). Leave blank for default = @default.', ['@default' => $field_settings['suffix']]),
      '#placeholder' => $field_settings['suffix'],
    );

    // Field base #min setting could be increased and #max decreased only.
    $min = is_numeric($field_settings['min']) ? $field_settings['min'] : (!empty($field_settings['unsigned']) ? '0' : '');
    $max = is_numeric($field_settings['max']) ? $field_settings['max'] : '';
    $min = (string) $min;
    $max = (string) $max;
    foreach ($element as $name => $value) {
      if ($value['#type'] == 'number') {
        if (is_numeric($min) && $name != 'step') {
          $element[$name]['#min'] = $min;
        }
        if ($num = is_numeric($max)) {
          $element[$name]['#max'] = $max;
        }
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $none = t('None');
    $settings = $this->getSettings();
    $form_settings = $this->getFormDisplayModeSettings();
    foreach ($form_settings as $name => $value) {
      $value = $settings[$name] == '' && $form_settings[$name] == '' ? $none : $value;
      $summary[] = "{$name}: {$value}";
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? (string) $items[$delta]->value : NULL;
    $settings = $this->getFormDisplayModeSettings();

    $element += array(
      '#type' => 'number',
      '#default_value' => is_numeric($settings['default_value']) ? $settings['default_value'] : $value,
      '#placeholder' => $settings['placeholder'],
      '#step' => $settings['step'],
    );

    // Set minimum and maximum.
    if (is_numeric($settings['min'])) {
      $element['#min'] = $settings['min'];
    }
    if (is_numeric($settings['max'])) {
      $element['#max'] = $settings['max'];
    }

    // Add prefix and suffix.
    // @todo: consider to not restrict prefix and suffix only to the last
    // plural value (singular|plural) making it accessible for AJAX/JS handlers.
    if ($settings['prefix']) {
      $prefixes = explode('|', $settings['prefix']);
      $element['#field_prefix'] = FieldFilteredMarkup::create(array_pop($prefixes));
    }
    if ($settings['suffix']) {
      $suffixes = explode('|', $settings['suffix']);
      $element['#field_suffix'] = FieldFilteredMarkup::create(array_pop($suffixes));
    }

    return array('value' => $element);
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    return $element['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormDisplayModeSettings() {
    $field_settings = $this->getFieldSettings();
    $settings = $this->getSettings();
    $default_value = array_column($this->fieldDefinition->getDefaultValueLiteral(), 'value');
    $field_settings['default_value'] = isset($default_value[0]) ? $default_value[0] : '';

    foreach ($settings as $key => $value) {
      if ($value == '') {
        $settings[$key] = isset($field_settings[$key]) ? $field_settings[$key] : '';
      }
    }

    $settings['min'] = is_numeric($settings['min']) ? $settings['min'] : '';
    $settings['max'] = is_numeric($settings['max']) ? $settings['max'] : '';

    switch ($this->fieldDefinition->getType()) {
      case 'integer':
      case 'float':
        $settings['step'] = is_numeric($settings['step']) ? $settings['step'] : '1';
        break;

      case 'decimal':
        $scale = empty($field_settings['scale']) ? 2 : $field_settings['scale'];
        $settings['step'] = is_numeric($settings['step']) ? $settings['step'] : pow(0.1, $scale);
        break;

    }
    $unsigned = is_numeric($settings['min']) && $settings['min'] >= 0;
    $settings['min'] = !empty($field_settings['unsigned']) && !$unsigned ? '0' : $settings['min'];

    return $settings;
  }

}
