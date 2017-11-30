<?php

namespace Drupal\commerce_order\Plugin\Field\FieldWidget;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_price\Price;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of 'commerce_adjustment_default'.
 *
 * @FieldWidget(
 *   id = "commerce_adjustment_default",
 *   label = @Translation("Adjustment"),
 *   field_types = {
 *     "commerce_adjustment"
 *   }
 * )
 */
class AdjustmentDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_order\Adjustment $adjustment */
    $adjustment = $items[$delta]->value;

    $element['#attached']['library'][] = 'commerce_price/admin';

    /** @var \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.commerce_adjustment_type');

    $types = [
      '_none' => $this->t('- Select -'),
    ];
    foreach ($plugin_manager->getDefinitions() as $id => $definition) {
      if ($definition['has_ui'] == TRUE) {
        $types[$id] = $definition['label'];
      }
    }

    $element['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => $types,
      '#weight' => 1,
      '#default_value' => ($adjustment) ? $adjustment->getType() : '_none',
    ];

    // If this is being added through the UI, the source ID should be empty,
    // and we will want to default it to custom.
    $source_id = ($adjustment) ? $adjustment->getSourceId() : NULL;
    $element['source_id'] = [
      '#type' => 'value',
      '#value' => empty($source_id) ? 'custom' : $source_id,
    ];
    // If this is being added through the UI, the adjustment should be locked.
    // UI added adjustments need to be locked to persist after an order refresh.
    $element['locked'] = [
      '#type' => 'value',
      '#value' => ($adjustment) ? $adjustment->isLocked() : TRUE,
    ];

    $states_selector_name = $this->fieldDefinition->getName() . "[$delta][type]";
    $element['definition'] = [
      '#type' => 'container',
      '#weight' => 2,
      '#states' => [
        'invisible' => [
          'select[name="' . $states_selector_name . '"]' => ['value' => '_none'],
        ],
      ],
    ];
    $element['definition']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => ($adjustment) ? $adjustment->getLabel() : '',
      '#required' => TRUE,
    ];
    $element['definition']['amount'] = [
      '#type' => 'commerce_price',
      '#title' => t('Amount'),
      '#default_value' => ($adjustment) ? $adjustment->getAmount()->toArray() : NULL,
      '#allow_negative' => TRUE,
      '#states' => [
        'optional' => [
          'select[name="' . $states_selector_name . '"]' => ['value' => '_none'],
        ],
      ],
    ];
    $element['definition']['included'] = [
      '#type' => 'checkbox',
      '#title' => t('Included in the base price'),
      '#default_value' => ($adjustment) ? $adjustment->isIncluded() : FALSE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $key => $value) {
      if ($value['type'] == '_none') {
        continue;
      }
      // The method can be called with invalid or incomplete data, in
      // preparation for validation. Passing such data to the Adjustment
      // object would result in an exception.
      if (empty($value['definition']['label'])) {
        continue;
      }

      $values[$key] = new Adjustment([
        'type' => $value['type'],
        'label' => $value['definition']['label'],
        'amount' => new Price($value['definition']['amount']['number'], $value['definition']['amount']['currency_code']),
        'source_id' => $value['source_id'],
        'included' => $value['definition']['included'],
        'locked' => $value['locked'],
      ]);
    }
    return $values;
  }

}
