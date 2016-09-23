<?php

namespace Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer;

/**
 * Provides a 'Product: Percentage off' condition.
 *
 * @CommercePromotionOffer(
 *   id = "commerce_promotion_product_percentage_off",
 *   label = @Translation("Percentage off"),
 *   target_entity_type = "commerce_order_item",
 * )
 */
class ProductPercentageOff extends PercentageOffBase {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $this->getTargetEntity();
    $price_amount = $order_item->getUnitPrice()->multiply($this->getAmount());
    $this->applyAdjustment($order_item, $price_amount);
  }

}
