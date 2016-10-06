<?php

namespace Drupal\commerce\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\NumberWidget;

/**
 * Plugin implementation of the 'mode_number' widget.
 *
 * @FieldWidget(
 *   id = "mode_number",
 *   label = @Translation("Mode number field"),
 *   field_types = {
 *     "integer",
 *     "decimal",
 *     "float"
 *   }
 * )
 */
class ModeNumberWidget extends NumberWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'placeholder' => NULL,
      'min' => NULL,
      'max' => NULL,
      'default_value' => NULL,
      'step' => NULL,
      'prefix' => NULL,
      'suffix' => NULL,
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $field_settings = $this->getFieldSettings();
    $settings = $this->getModeNumberFieldSettings();
    $scale = empty($field_settings['scale']) ? 0 : $field_settings['scale'];
    $step = '1';
    $notes = '';

    // Set a minimal valid step to set settings for the corresponding field type.
    switch ($this->fieldDefinition->getType()) {
      case 'decimal':
        $step = (string) pow(0.1, $scale);
        $n = $nn = 'n';
        $format = ['"' . $n . '"'];
        while ($field_settings['scale']--) {
          array_push($format, '"' . $nn . '"');
          $nn = "$n$nn";
        }
        $notes = t(' Restricts the number of digits after decimal sign to the given step format. For this field instance format patterns are the following: @format. Note that omitting the decimal sign in this setting restricts input on the field to integer values despite the actual field type is decimal.', ['@format' => implode(', ', $format)]);
        break;

      case 'float':
        $step = 'any';
        $notes = t(' Note that built in step is integer "1" but input on the field could be done in any float or integer format: "n", "n.n", "n.nn", "n.nnn", "n.nnnn", etc..');
        break;
    }

    $element['placeholder'] = array(
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->t($settings['placeholder']),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
      '#placeholder' => t('None'),
    );
    $element['default_value'] = array(
      '#type' => 'number',
      '#title' => t('Default value'),
      '#step' => $step,
      '#default_value' => (string) $settings['default_value'],
      '#description' => t("The default value for this field. Leave blank for none."),
      '#placeholder' => t('None'),
    );
    $element['min'] = array(
      '#type' => 'number',
      '#title' => t('Minimum'),
      '#step' => $step,
      '#default_value' => (string) $settings['min'],
      '#description' => t('The minimum value that should be allowed in this field. Leave blank for no minimum.'),
      '#placeholder' => t('No minimum'),
    );
    $element['max'] = array(
      '#type' => 'number',
      '#step' => $step,
      '#title' => t('Maximum'),
      '#default_value' => (string) $settings['max'],
      '#description' => t('The maximum value that should be allowed in this field. Leave blank for no maximum.'),
      '#placeholder' => t('No maximum'),
    );
    $element['step'] = array(
      '#type' => 'number',
      '#min' => is_numeric($step) ? $step : '0',
      '#step' => $step,
      '#title' => t('Step'),
      '#default_value' => (string) $settings['step'],
      '#description' => t('The minimum allowed amount to increment or decrement the field value with.') . $notes,
      '#placeholder' => $step == 'any' ? t('Any!') : t('Required!'),
      '#required' => $step != 'any',
    );
    $element['prefix'] = array(
      '#type' => 'textfield',
      '#title' => t('Prefix'),
      '#default_value' => $this->t($settings['prefix']),
      '#description' => t("Define a string that should be prefixed to the value, like '$ ' or '&euro; '. Leave blank for none."),
      '#placeholder' => t('None'),
    );
    $element['suffix'] = array(
      '#type' => 'textfield',
      '#title' => t('Suffix'),
      '#default_value' => $this->t($settings['suffix']),
      '#description' => t("Define a string that should be suffixed to the value, like ' m', ' kb/s'. Leave blank for none."),
      '#placeholder' => t('None'),
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
        if ($num = is_numeric($max) && $name != 'step') {
          $element[$name]['#max'] = $max;
        }
        elseif ($name == 'step') {
          // The reasonable #max for the step itself have to be twice less than
          // #max - #min with redundant digits after decimal sign truncated.
          $max_step = is_numeric($settings['max']) ? $settings['max'] : ($num ? $max : ($step + 1) * 10);
          $element[$name]['#max'] = (string) floor((($max_step - $min) / 2) * pow(10, $scale)) / pow(10, $scale);
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
    $settings = $this->getModeNumberFieldSettings();
    foreach ($settings as $name => $value) {
      $summary[] = "$name: $value";
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $settings = $this->getSettings();

    $element += array(
      '#type' => 'number',
      '#placeholder' => $settings['placeholder'],
      '#step' => empty($settings['step']) ? 'any' : $settings['step'],
    );

    // Set default, minimum and maximum.
    if (is_numeric($settings['default_value'])) {
      $element['#default_value'] = $settings['default_value'];
    }
    if (is_numeric($settings['min'])) {
      $element['#min'] = $settings['min'];
    }
    if (is_numeric($settings['max'])) {
      $element['#max'] = $settings['max'];
    }

    // Add prefix and suffix.
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
  public function getModeNumberFieldSettings() {
    $field_settings = $this->getFieldSettings();
    $settings = $this->getSettings();

    // Field base settings are only used when saving a mode for the first time.
    if (is_null($settings['default_value'])) {
      $default_value = array_column($this->fieldDefinition->getDefaultValueLiteral(), 'value');
      $field_settings['default_value'] = isset($default_value[0]) ? $default_value[0] : '';
    }

    foreach ($settings as $key => $value) {
      if (is_null($value)) {
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

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    return $element['value'];
  }

}
