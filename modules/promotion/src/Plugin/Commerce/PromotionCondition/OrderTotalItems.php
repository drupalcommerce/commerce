<?php

namespace Drupal\commerce_promotion\Plugin\Commerce\PromotionCondition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_price\Price;

/**
 * Provides an 'Order: Total items' condition.
 *
 * @CommercePromotionCondition(
 *   id = "commerce_promotion_order_total_items",
 *   label = @Translation("Total items"),
 *   target_entity_type = "commerce_order",
 * )
 */
class OrderTotalItems extends PromotionConditionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'qty' => NULL,
      'operator' => '>=',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['operator'] = [
      '#type' => 'select',
      '#title' => t('Operator'),
      '#options' => $this->getComparisonOperators(),
      '#default_value' => $this->configuration['operator'],
      '#required' => TRUE,
    ];
    $form['qty'] = [
      '#type' => 'number',
      '#step' => 1,
      '#title' => t('Quantity'),
      '#default_value' => $this->configuration['qty'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    $this->configuration['qty'] = $values['qty'];
    $this->configuration['operator'] = $values['operator'];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $qty = $this->configuration['qty'];
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->getTargetEntity();
    $total_items = 0;
    foreach ($order->getItems() as $item) {
      $total_items += (int) $item->getQuantity();
    }

    switch ($this->configuration['operator']) {
      case '>=':
        return $total_items >= $qty;

      case '>':
        return $total_items > $qty;

      case '<=':
        return $total_items <= $qty;

      case '<':
        return $total_items < $qty;

      case '==':
        return $total_items == $qty;

      default:
        return FALSE;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Compares the total quantity of order items.');
  }

}
