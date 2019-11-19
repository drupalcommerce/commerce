<?php

/**
 * @file
 * Post update functions for Promotion.
 */

use Drupal\commerce_promotion\Entity\PromotionInterface;
use Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer\OrderItemPromotionOfferInterface;
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

/**
 * Update offers and conditions.
 */
function commerce_promotion_post_update_9(&$sandbox = NULL) {
  $promotion_storage = \Drupal::entityTypeManager()->getStorage('commerce_promotion');
  if (!isset($sandbox['current_count'])) {
    $query = $promotion_storage->getQuery();
    $sandbox['total_count'] = $query->count()->execute();
    $sandbox['current_count'] = 0;
    $sandbox['disabled_offers'] = [];
    $sandbox['disabled_conditions'] = [];

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
    $needs_disable = FALSE;

    $conditions = $promotion->getConditions();
    $order_item_conditions = array_filter($conditions, function ($condition) {
      /** @var \Drupal\commerce\Plugin\Commerce\Condition\ConditionInterface $condition */
      return $condition->getEntityTypeId() == 'commerce_order_item' && $condition->getPluginId() != 'order_item_quantity';
    });
    $condition_map = [
      'order_item_product' => 'order_product',
      'order_item_product_type' => 'order_product_type',
      'order_item_variation_type' => 'order_variation_type',
    ];
    $condition_items = $promotion->get('conditions')->getValue();

    $known_order_item_offers = [
      'order_item_fixed_amount_off',
      'order_item_percentage_off',
    ];
    $offer = $promotion->getOffer();
    $offer_item = $promotion->get('offer')->first()->getValue();

    if ($offer->getEntityTypeId() == 'commerce_order_item') {
      $needs_save = TRUE;
      // Transfer order item conditions to the offer.
      // Modify the offer item directly to be able to upgrade offers that
      // haven't yet been converted to extend OfferItemPromotionOfferBase.
      $offer_item['target_plugin_configuration']['conditions'] = [];
      foreach ($order_item_conditions as $condition) {
        $offer_item['target_plugin_configuration']['conditions'][] = [
          'plugin' => $condition->getPluginId(),
          'configuration' => $condition->getConfiguration(),
        ];
      }

      // The promotion is using a custom offer which hasn't been updated yet,
      // disable it so that it can get updated without crashing everything.
      if (!in_array($offer->getPluginId(), $known_order_item_offers)) {
        if (!($offer instanceof OrderItemPromotionOfferInterface)) {
          $needs_disable = TRUE;
          $sandbox['disabled_offers'][] = $promotion->label();
        }
      }
    }

    // Convert known order item conditions to order conditions.
    if ($order_item_conditions) {
      foreach ($condition_items as $index => $condition_item) {
        if (array_key_exists($condition_item['target_plugin_id'], $condition_map)) {
          $condition_items[$index]['target_plugin_id'] = $condition_map[$condition_item['target_plugin_id']];
          $needs_save = TRUE;
        }
      }
      $promotion->set('conditions', $condition_items);
    }

    // Drop unknown order item conditions.
    $conditions = $promotion->getConditions();
    $order_item_conditions = array_filter($conditions, function ($condition) {
      /** @var \Drupal\commerce\Plugin\Commerce\Condition\ConditionInterface $condition */
      return $condition->getEntityTypeId() == 'commerce_order_item' && $condition->getPluginId() != 'order_item_quantity';
    });
    foreach ($order_item_conditions as $condition) {
      foreach ($condition_items as $index => $condition_item) {
        if ($condition_item['target_plugin_id'] == $condition->getPluginId()) {
          unset($condition_items[$index]);
          $needs_save = TRUE;
          // An unrecognized offer was dropped, but because the offer applies
          // to the order, wasn't transferred there. Disable the promotion
          // to allow the merchant to double check the new configuration.
          if ($offer->getEntityTypeId() == 'commerce_order') {
            $needs_disable = TRUE;
            $sandbox['disabled_conditions'][$promotion->id()] = [$promotion->label(), $condition->getPluginId()];
          }
        }
      }
    }

    if ($needs_disable) {
      $promotion->setEnabled(FALSE);
    }
    if ($needs_save) {
      $promotion->set('offer', $offer_item);
      $promotion->set('conditions', array_values($condition_items));
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

  if ($sandbox['#finished']) {
    $message = '';
    if ($sandbox['disabled_offers']) {
      $message .= 'These promotions have been disabled because their offers need to be updated for Commerce 2.8: <br>';
      foreach ($sandbox['disabled_offers'] as $promotion_title) {
        $message .= '- ' . $promotion_title . '<br>';
      }
    }
    if ($sandbox['disabled_conditions']) {
      $message .= 'These promotions have been disabled because their conditions need to be updated for Commerce 2.8: <br>';
      foreach ($sandbox['disabled_conditions'] as $item) {
        $message .= '- ' . $item[0] . ' (Condition: ' . $item[1] . ') <br>';
      }
    }
    if ($message) {
      $message .= 'Please see https://www.drupal.org/node/2982334 for more information.';
    }
    else {
      $message .= 'Successfully updated all promotions';
    }

    return $message;
  }
}

/**
 * Re-save order item promotions to populate the display_included field.
 */
function commerce_promotion_post_update_10(&$sandbox = NULL) {
  $offer_ids = [
    'order_item_fixed_amount_off',
    'order_item_percentage_off',
  ];
  $promotion_storage = \Drupal::entityTypeManager()->getStorage('commerce_promotion');
  if (!isset($sandbox['current_count'])) {
    $query = $promotion_storage->getQuery();
    $query->condition('offer.target_plugin_id', $offer_ids, 'IN');
    $sandbox['total_count'] = $query->count()->execute();
    $sandbox['current_count'] = 0;

    if (empty($sandbox['total_count'])) {
      $sandbox['#finished'] = 1;
      return;
    }
  }

  $query = $promotion_storage->getQuery();
  $query->condition('offer.target_plugin_id', $offer_ids, 'IN');
  $query->range($sandbox['current_count'], 25);
  $result = $query->execute();
  if (empty($result)) {
    $sandbox['#finished'] = 1;
    return;
  }

  /** @var \Drupal\commerce_promotion\Entity\PromotionInterface[] $promotions */
  $promotions = $promotion_storage->loadMultiple($result);
  foreach ($promotions as $promotion) {
    // Work on the raw plugin item to avoid defaults being merged in.
    $offer_item = $promotion->get('offer')->first();
    $configuration = $offer_item->target_plugin_configuration;
    if (!isset($configuration['display_inclusive'])) {
      $configuration['display_inclusive'] = FALSE;
      $offer_item->target_plugin_configuration = $configuration;
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
 * Allows promotion start and end dates to have a time component.
 */
function commerce_promotion_post_update_11(array &$sandbox = NULL) {
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
  $query->range($sandbox['current_count'], 50);
  $result = $query->execute();
  if (empty($result)) {
    $sandbox['#finished'] = 1;
    return;
  }

  /** @var \Drupal\commerce_promotion\Entity\Promotion[] $promotions */
  $promotions = $promotion_storage->loadMultiple($result);
  foreach ($promotions as $promotion) {
    // Re-set each date to ensure it is stored in the updated format.
    // Increase the end date by a day to match old inclusive loading
    // (where an end date was valid until 23:59:59 of that day).
    $start_date = $promotion->getStartDate();
    $end_date = $promotion->getEndDate();
    if ($end_date) {
      $end_date = $end_date->modify('+1 day');
    }
    $promotion->setStartDate($start_date);
    $promotion->setEndDate($end_date);

    $promotion->save();
  }

  $sandbox['current_count'] += 50;
  if ($sandbox['current_count'] >= $sandbox['total_count']) {
    $sandbox['#finished'] = 1;
  }
  else {
    $sandbox['#finished'] = ($sandbox['total_count'] - $sandbox['current_count']) / $sandbox['total_count'];
  }
}
