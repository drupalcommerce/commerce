<?php

/**
 * @file
 * Post update functions for Promotion.
 */

use Drupal\commerce_promotion\Entity\PromotionInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Add the coupons field to orders.
 */
function commerce_promotion_post_update_1() {
  $entity_definition_update = \Drupal::entityDefinitionUpdateManager();
  $order_definition = $entity_definition_update->getEntityType('commerce_order');
  $fields = commerce_promotion_entity_base_field_info($order_definition);
  $entity_definition_update->installFieldStorageDefinition('coupons', 'commerce_order', 'commerce_promotion', $fields['coupons']);
}

/**
 * Add the 'promotion_id' field to coupons.
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

  /** @var \Drupal\commerce_promotion\PromotionStorageInterface $promotion_storage */
  $promotion_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_promotion');
  $promotions = $promotion_storage->loadMultiple();
  foreach ($promotions as $promotion) {
    // Promotion::preSave() will populate the new field.
    $promotion->save();
  }
}

/**
 * Delete orphaned coupons.
 */
function commerce_promotion_post_update_3() {
  /** @var \Drupal\commerce_promotion\CouponStorageInterface $coupon_storage */
  $coupon_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_promotion_coupon');
  /** @var \Drupal\commerce_promotion\Entity\CouponInterface[] $coupons */
  $coupons = $coupon_storage->loadMultiple();
  $delete_coupons = [];
  foreach ($coupons as $coupon) {
    if (!$coupon->getPromotion()) {
      $delete_coupons[] = $coupon;
    }
  }
  $coupon_storage->delete($delete_coupons);
}

/**
 * Add the compatibility field to promotions.
 */
function commerce_promotion_post_update_4() {
  $storage_definition = BaseFieldDefinition::create('list_string')
    ->setLabel(t('Compatibility with other promotions'))
    ->setSetting('allowed_values_function', ['\Drupal\commerce_promotion\Entity\Promotion', 'getCompatibilityOptions'])
    ->setRequired(TRUE)
    ->setDefaultValue(PromotionInterface::COMPATIBLE_ANY)
    ->setDisplayOptions('form', [
      'type' => 'options_select',
      'weight' => 4,
    ]);

  $entity_definition_update = \Drupal::entityDefinitionUpdateManager();
  $entity_definition_update->installFieldStorageDefinition('compatibility', 'commerce_promotion', 'commerce_promotion', $storage_definition);

  /** @var \Drupal\commerce_promotion\PromotionStorageInterface $promotion_storage */
  $promotion_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_promotion');
  /** @var \Drupal\commerce_promotion\Entity\PromotionInterface[] $promotions */
  $promotions = $promotion_storage->loadMultiple();
  foreach ($promotions as $promotion) {
    $promotion->setCompatibility(PromotionInterface::COMPATIBLE_ANY);
    $promotion->save();
  }
}
