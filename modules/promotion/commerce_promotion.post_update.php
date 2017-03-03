<?php

/**
 * @file
 * Post update functions for Promotion.
 */

use Drupal\Core\Field\BaseFieldDefinition;
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
 * Add the 'promotion_id' field to 'commerce_promotion_coupon' entities
 * and update existing coupons data.
 */
function commerce_promotion_post_update_2() {
  $storage_definition = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Promotion'))
    ->setDescription(t('The parent promotion.'))
    ->setSetting('target_type', 'commerce_promotion')
    ->setReadOnly(TRUE)
    ->setDisplayConfigurable('view', TRUE);

  $update_manager = \Drupal::entityDefinitionUpdateManager();
  $update_manager->installFieldStorageDefinition('promotion_id', 'commerce_promotion_coupon', 'commerce_promotion', $storage_definition);

  // Updates on existing data.
  /** @var \Drupal\commerce_promotion\PromotionStorageInterface $promotion_storage */
  $promotion_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_promotion');
  /** @var \Drupal\commerce_promotion\Entity\PromotionInterface[] $promotions */
  $promotions = $promotion_storage->loadMultiple();
  foreach ($promotions as $promotion) {
    // Save promotion to update the coupon promotion id field.
    $promotion->save();
  }
}
