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

    $element['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => [
        '_none' => $this->t('- Select -'),
        'custom' => $this->t('Apply order adjustment'),
      ],
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

    $states_selector_name = $this->fieldDefinition->getName() . "[$delta][type]";
    $element['definition'] = [
      '#type' => 'container',
      '#weight' => 2,
      '#states' => [
        'visible' => [
          'select[name="' . $states_selector_name . '"]' => ['value' => 'custom'],
        ],
      ],
    ];
    $element['definition']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => ($adjustment) ? $adjustment->getLabel() : '',
    ];

    $element['definition']['amount'] = [
      '#type' => 'commerce_price',
      '#title' => t('Amount'),
      '#default_value' => ($adjustment) ? $adjustment->getAmount()->toArray() : NULL,
      '#states' => [
        'required' => [
          'select[name="' . $states_selector_name . '"]' => ['value' => 'custom'],
        ],
      ],
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

      $values[$key] = new Adjustment([
        'type' => $value['type'],
        'label' => $value['definition']['label'],
        'amount' => new Price($value['definition']['amount']['number'], $value['definition']['amount']['currency_code']),
        'source_id' => $value['source_id'],
      ]);
    }
    return $values;
  }

}
