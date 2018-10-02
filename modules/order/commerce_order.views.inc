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

/**
 * Implements hook_views_data_alter().
 */
function commerce_order_views_data_alter(array &$data) {
  $data['commerce_order']['store_id']['field']['id'] = 'commerce_store';
  $data['commerce_order']['state']['filter']['id'] = 'state_machine_state';
  $data['commerce_order_item']['purchased_entity']['field']['id'] = 'commerce_order_item_purchased_entity';
}
