<?php

namespace Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer;

/**
 * Provides the 'Order: Fixed amount off' offer.
 *
 * @CommercePromotionOffer(
 *   id = "order_fixed_amount_off",
 *   label = @Translation("Fixed amount off the order total"),
 * )
 */
class OrderFixedAmountOff extends FixedAmountOffBase {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->getOrder();
    $total_price = $order->getTotalPrice();
    $promotion_amount = $this->getAmount();
    if ($total_price->getCurrencyCode() != $promotion_amount->getCurrencyCode()) {
      return;
    }

    // Don't reduce the order total past zero.
    if ($promotion_amount->greaterThan($total_price)) {
      $promotion_amount = $total_price;
    }
    $this->applyAdjustment($order, $promotion_amount);
  }

}
