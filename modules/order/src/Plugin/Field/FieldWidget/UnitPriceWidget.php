<?php

namespace Drupal\commerce_order\Plugin\Field\FieldWidget;

use Drupal\commerce_price\Price;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'commerce_unit_price' widget.
 *
 * @FieldWidget(
 *   id = "commerce_unit_price",
 *   label = @Translation("Unit price"),
 *   field_types = {
 *     "commerce_price",
 *   }
 * )
 */
class UnitPriceWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'require_confirmation' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['require_confirmation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Require confirmation before overriding the unit price'),
      '#default_value' => $this->getSetting('require_confirmation'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    if ($this->getSetting('require_confirmation') == 1) {
      $summary[] = $this->t('Require confirmation before overriding the unit price');
    }
    else {
      $summary[] = $this->t('Do not require confirmation before overriding the unit price');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = [];
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $items[$delta]->getEntity();
    if ($this->getSetting('require_confirmation')) {
      $element['override'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Override the unit price'),
        '#default_value' => $order_item->isUnitPriceOverridden(),
      ];
    }

    $element['amount'] = [
      '#type' => 'commerce_price',
      '#title' => $this->fieldDefinition->getLabel(),
      '#required' => $this->fieldDefinition->isRequired(),
      '#available_currencies' => array_filter($this->getFieldSetting('available_currencies')),
    ];
    if (!$items[$delta]->isEmpty()) {
      $element['amount']['#default_value'] = $items[$delta]->toPrice()->toArray();
    }
    if ($this->getSetting('require_confirmation')) {
      $checkbox_parents = array_merge($form['#parents'], [$this->fieldDefinition->getName(), 0, 'override']);
      $checkbox_path = array_shift($checkbox_parents);
      $checkbox_path .= '[' . implode('][', $checkbox_parents) . ']';

      $element['amount']['#states'] = [
        'visible' => [
          ':input[name="' . $checkbox_path . '"]' => ['checked' => TRUE],
        ],
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $path = array_merge($form['#parents'], [$field_name, 0]);
    $values = NestedArray::getValue($form_state->getValues(), $path);
    if ($values) {
      /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
      $order_item = $items[0]->getEntity();
      if (!$this->getSetting('require_confirmation') || !empty($values['override'])) {
        $unit_price = new Price($values['amount']['number'], $values['amount']['currency_code']);
        $order_item->setUnitPrice($unit_price, TRUE);
      }

      // Put delta mapping in $form_state, so that flagErrors() can use it.
      $field_state = static::getWidgetState($form['#parents'], $field_name, $form_state);
      foreach ($items as $delta => $item) {
        $field_state['original_deltas'][$delta] = $delta;
      }
      static::setWidgetState($form['#parents'], $field_name, $form_state, $field_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $entity_type == 'commerce_order_item' && $field_name == 'unit_price';
  }

}
