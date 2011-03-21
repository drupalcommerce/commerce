<?php

/**
 * Provide shopping cart related Views integration.
 */

/**
 * Implements hook_views_data()
 */
function commerce_cart_views_data_alter(&$data) {
  $data['commerce_product']['add_to_cart_form'] = array(
    'field' => array(
      'title' => t('Add to Cart form'),
      'help' => t('Display an Add to Cart form for the product.'),
      'handler' => 'commerce_cart_handler_field_add_to_cart_form',
    ),
  );
}
