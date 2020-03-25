<?php

/**
 * @file
 * Provide views data for the Promotion module.
 */

/**
 * Implements hook_views_data().
 */
function commerce_promotion_views_data() {
  $data['views']['commerce_coupon_redemption'] = [
    'title' => t('Coupon redemption'),
    'help' => t('Displays a coupon redemption pane, requires an Order ID argument.'),
    'area' => [
      'id' => 'commerce_coupon_redemption',
    ],
  ];
  return $data;
}
