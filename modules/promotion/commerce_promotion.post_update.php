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

/**
 * Update offers and conditions.
 */
function commerce_promotion_post_update_6(&$sandbox = NULL) {
  $promotion_storage = \Drupal::entityTypeManager()->getStorage('commerce_promotion');
  if (!isset($sandbox['current_count'])) {
    $query = $promotion_storage->getQuery();
    $sandbox['total_count'] = $query->count()->execute();
    $sandbox['current_count'] = 0;

    if (empty($sandbox['total_count'])) {
      $sandbox['#finished'] = 1;
      return;
    }
  }

  $query = $promotion_storage->getQuery();
  $query->range($sandbox['current_count'], 25);
  $result = $query->execute();
  if (empty($result)) {
    $sandbox['#finished'] = 1;
    return;
  }

  /** @var \Drupal\commerce_promotion\Entity\PromotionInterface[] $promotions */
  $promotions = $promotion_storage->loadMultiple($result);
  foreach ($promotions as $promotion) {
    $needs_save = FALSE;
    $conditions = $promotion->get('conditions')->getValue();
    foreach ($conditions as &$condition_item) {
      if ($condition_item['target_plugin_id'] == 'commerce_promotion_order_total_price') {
        $condition_item['target_plugin_id'] = 'order_total_price';
        // Remove data added by the old conditions API.
        unset($condition_item['target_plugin_configuration']['id']);
        unset($condition_item['target_plugin_configuration']['negate']);
        $needs_save = TRUE;
      }
    }

    $offer = $promotion->get('offer')->first()->getValue();
    if ($offer['target_plugin_id'] == 'commerce_promotion_order_percentage_off') {
      $offer['target_plugin_id'] = 'order_percentage_off';
      $needs_save = TRUE;
    }
    elseif ($offer['target_plugin_id'] = 'commerce_promotion_product_percentage_off') {
      $offer['target_plugin_id'] = 'order_item_percentage_off';
      // The product_id setting has been removed and needs to be migrated to a condition.
      $product_id = $offer['target_plugin_configuration']['product_id'];
      unset($offer['target_plugin_configuration']['product_id']);
      $has_existing_condition = FALSE;
      foreach ($conditions as &$condition_item) {
        if ($condition_item['target_plugin_id'] == 'order_item_product') {
          $condition_item['target_plugin_configuration']['products'][] = ['product_id' => $product_id];
          $condition_item['target_plugin_configuration']['products'] = array_unique($condition_item['target_plugin_configuration']['products']);
          $has_existing_condition = TRUE;
        }
      }
      if (!$has_existing_condition) {
        $conditions[] = [
          'target_plugin_id' => 'order_item_product',
          'target_plugin_configuration' => [
            'products' => [
              ['product_id' => $product_id],
            ],
          ],
        ];
      }
      $needs_save = TRUE;
    }

    if ($needs_save) {
      $promotion->set('offer', $offer);
      $promotion->set('conditions', $conditions);
      $promotion->save();
    }
  }

  $sandbox['current_count'] += 25;
  if ($sandbox['current_count'] >= $sandbox['total_count']) {
    $sandbox['#finished'] = 1;
  }
  else {
    $sandbox['#finished'] = ($sandbox['total_count'] - $sandbox['current_count']) / $sandbox['total_count'];
  }
}

/**
 * Add the condition_operator field to promotions.
 */
function commerce_promotion_post_update_7() {
  $storage_definition = BaseFieldDefinition::create('list_string')
    ->setLabel(t('Condition operator'))
    ->setDescription(t('The condition operator.'))
    ->setRequired(TRUE)
    ->setSetting('allowed_values', [
      'AND' => t('All conditions must pass'),
      'OR' => t('Only one condition must pass'),
    ])
    ->setDisplayOptions('form', [
      'type' => 'options_buttons',
      'weight' => 4,
    ])
    ->setDisplayConfigurable('form', TRUE)
    ->setDefaultValue('AND');

  $entity_definition_update = \Drupal::entityDefinitionUpdateManager();
  $entity_definition_update->installFieldStorageDefinition('condition_operator', 'commerce_promotion', 'commerce_promotion', $storage_definition);
}

/**
 * Re-save promotions to populate the condition operator field.
 */
function commerce_promotion_post_update_8(&$sandbox = NULL) {
  $promotion_storage = \Drupal::entityTypeManager()->getStorage('commerce_promotion');
  if (!isset($sandbox['current_count'])) {
    $query = $promotion_storage->getQuery();
    $sandbox['total_count'] = $query->count()->execute();
    $sandbox['current_count'] = 0;

    if (empty($sandbox['total_count'])) {
      $sandbox['#finished'] = 1;
      return;
    }
  }

  $query = $promotion_storage->getQuery();
  $query->range($sandbox['current_count'], 25);
  $result = $query->execute();
  if (empty($result)) {
    $sandbox['#finished'] = 1;
    return;
  }

  /** @var \Drupal\commerce_promotion\Entity\PromotionInterface[] $promotions */
  $promotions = $promotion_storage->loadMultiple($result);
  foreach ($promotions as $promotion) {
    $promotion->setConditionOperator('AND');
    $promotion->save();
  }

  $sandbox['current_count'] += 25;
  if ($sandbox['current_count'] >= $sandbox['total_count']) {
    $sandbox['#finished'] = 1;
  }
  else {
    $sandbox['#finished'] = ($sandbox['total_count'] - $sandbox['current_count']) / $sandbox['total_count'];
  }
}
