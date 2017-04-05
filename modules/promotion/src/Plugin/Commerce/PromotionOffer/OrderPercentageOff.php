<?php

namespace Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer;

/**
 * Provides an 'Order: Percentage off' condition.
 *
 * @CommercePromotionOffer(
 *   id = "commerce_promotion_order_percentage_off",
 *   label = @Translation("Percentage amount off of the order total"),
 *   target_entity_type = "commerce_order",
 * )
 */
class OrderPercentageOff extends PercentageOffBase {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $order = $this->getOrder();
    $adjustment_amount = $order->getTotalPrice()->multiply($this->getAmount());
    $adjustment_amount = $this->rounder->round($adjustment_amount);
    $this->applyAdjustment($order, $adjustment_amount);
  }

}
