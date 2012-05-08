<?php

/**
 * Provide shopping cart related Views integration.
 */

/**
 * Implements hook_views_data_alter().
 */
function commerce_cart_views_data_alter(&$data) {
  $data['commerce_product']['add_to_cart_form'] = array(
    'field' => array(
      'title' => t('Add to Cart form'),
      'help' => t('Display an Add to Cart form for the product.'),
      'handler' => 'commerce_cart_handler_field_add_to_cart_form',
    ),
  );

  $data['commerce_order']['cart_empty_text'] = array(
    'title' => t('Empty Shopping Cart'),
    'help' => t('Displays an appropriate empty text message for shopping carts.'),
    'area' => array(
      'handler' => 'commerce_cart_handler_area_empty_text',
    ),
  );

}

/**
 * Implements hook_views_plugins().
 */
function commerce_cart_views_plugins() {
  return array(
    'argument default' => array(
      'commerce_cart_current_cart_order_id' => array(
        'title' => t("Current user's cart order ID"),
        'handler' => 'commerce_cart_plugin_argument_default_current_cart_order_id',
        'path' => drupal_get_path('module', 'commerce_cart') . '/includes/views/handlers'
      )
    )
  );
}
