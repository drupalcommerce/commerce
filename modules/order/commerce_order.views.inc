<?php

/**
 * @file
 * Provide views data that isn't tied to any other module.
 */

/**
 * Implements hook_views_data().
 */
function commerce_order_views_data() {
  $data['views']['commerce_order_total'] = array(
    'title' => t('Order total'),
    'help' => t('Displays the order total field formatted with its components list; requires an Order ID argument.'),
    'area' => array(
      'id' => 'commerce_order_total',
    ),
  );
  return $data;
}
