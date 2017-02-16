<?php

namespace Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer;

/**
 * Provides a 'Order: Percentage off' condition.
 *
 * @CommercePromotionOffer(
 *   id = "commerce_promotion_order_percentage_off",
 *   label = @Translation("Percentage off"),
 *   target_entity_type = "commerce_order",
 * )
 */
class OrderPercentageOff extends PercentageOffBase {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->getTargetEntity();
    $adjustment_amount = $order->getTotalPrice()->multiply($this->getAmount());
    $adjustment_amount = $this->rounder->round($adjustment_amount);
    $this->applyAdjustment($order, $adjustment_amount);
  }

}
