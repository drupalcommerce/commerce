<?php

namespace Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer;

/**
 * Provides a 'Product: Percentage off' condition.
 *
 * @PromotionOffer(
 *   id = "commerce_promotion_product_percentage_off",
 *   label = @Translation("Percentage off"),
 *   target_entity_type = "commerce_line_item",
 * )
 */
class ProductPercentageOff extends PercentageOffBase {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    /** @var \Drupal\commerce_order\Entity\LineItemInterface $line_item */
    $line_item = $this->getTargetEntity();
    $price_amount = $line_item->getTotalPrice()->multiply($this->getAmount());
    $this->applyAdjustment($line_item, $price_amount);
  }

}
