<?php

namespace Drupal\commerce_promotion_test\Plugin\Commerce\PromotionCondition;

use Drupal\commerce_promotion\Plugin\Commerce\PromotionCondition\PromotionConditionBase;

/**
 * Provides a 'Product variation SKU' condition.
 *
 * @CommercePromotionCondition(
 *   id = "commerce_promotion_test_variant_sku",
 *   label = @Translation("Product SKU is TEST123"),
 *   target_entity_type = "commerce_order_item",
 * )
 */
class TestSpecificSkuCondition extends PromotionConditionBase {

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $this->getTargetEntity();
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $purchased_entity */
    $purchased_entity = $order_item->getPurchasedEntity();

    return $purchased_entity->getSku() == 'TEST123';
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Tests a specific hardcoded SKU');
  }

}
