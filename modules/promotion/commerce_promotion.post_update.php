<?php

/**
 * @file
 * Post update functions for Promotion.
 */

/**
 * Adds coupons field to orders.
 */
function commerce_promotion_post_update_1() {
  $entity_definition_update = \Drupal::entityDefinitionUpdateManager();
  $order_definition = $entity_definition_update->getEntityType('commerce_order');
  $fields = commerce_promotion_entity_base_field_info($order_definition);
  $entity_definition_update->installFieldStorageDefinition('coupons', 'commerce_order', 'commerce_promotion', $fields['coupons']);
}

/**
 * Delete orphaned coupons.
 */
function commerce_promotion_post_update_2() {
  /** @var \Drupal\commerce_promotion\PromotionStorageInterface $promotion_storage */
  $promotion_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_promotion');
  /** @var \Drupal\commerce_promotion\Entity\PromotionInterface[] $promotions */
  $promotions = $promotion_storage->loadMultiple();
  $coupons_ids = [];
  foreach ($promotions as $promotion) {
    foreach ($promotion->get('coupons') as $coupons_item) {
      $coupons_ids[] = $coupons_item->target_id;
    }
  }

  $delete_coupons_ids = \Drupal::entityQuery('commerce_promotion_coupon')
    ->condition('id', $coupons_ids, 'NOT IN')
    ->execute();

  /** @var \Drupal\commerce_promotion\CouponStorageInterface $coupon_storage */
  $coupon_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_promotion_coupon');
  /** @var \Drupal\commerce_promotion\Entity\CouponInterface[] $coupons */
  $delete_coupons = $coupon_storage->loadMultiple($delete_coupons_ids);
  $coupon_storage->delete($delete_coupons);
}
