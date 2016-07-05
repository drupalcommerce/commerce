<?php

/**
 * @file
 * Provide views data for the Order module.
 */

/**
 * Implements hook_views_data().
 */
function commerce_order_views_data() {
  $data['views']['commerce_order_total'] = [
    'title' => t('Order total'),
    'help' => t('Displays the order total field, requires an Order ID argument.'),
    'area' => [
      'id' => 'commerce_order_total',
    ],
  ];
  return $data;
}
