<?php

namespace Drupal\commerce_promotion\Plugin\Commerce\PromotionCondition;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an 'Order: Total amount comparison' condition.
 *
 * @CommercePromotionCondition(
 *   id = "commerce_promotion_order_total_price",
 *   label = @Translation("Total amount"),
 *   target_entity_type = "commerce_order",
 * )
 */
class OrderTotalPrice extends PromotionConditionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'amount' => NULL,
      // @todo expose the operator in form.
      'operator' => '>',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form += parent::buildConfigurationForm($form, $form_state);

    $amount = $this->configuration['amount'];
    // A bug in the plugin_select form element causes $amount to be incomplete.
    if (isset($amount) && !isset($amount['number'], $amount['currency_code'])) {
      $amount = NULL;
    }

    $form['amount'] = [
      '#type' => 'commerce_price',
      '#title' => t('Amount'),
      '#default_value' => $amount,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->getTargetEntity();
    /** @var \Drupal\commerce_price\Price $total_price */
    $total_price = $order->getTotalPrice();
    /** @var \Drupal\commerce_price\Price $comparison_price */
    $comparison_price = $this->configuration['amount'];

    switch ($this->configuration['operator']) {
      case '==':
        return $total_price->equals($comparison_price);

      case '>=':
        return $total_price->greaterThanOrEqual($comparison_price);

      case '>':
        return $total_price->greaterThan($comparison_price);

      case '<=':
        return $total_price->lessThanOrEqual($comparison_price);

      case '<':
        return $total_price->lessThan($comparison_price);

      default:
        throw new \InvalidArgumentException("Invalid operator {$this->configuration['operator']}");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Compares the order total amount.');
  }

}
