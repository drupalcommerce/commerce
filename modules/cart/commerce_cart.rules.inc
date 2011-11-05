<?php

/**
 * @file
 * Rules integration for shopping carts.
 *
 * @addtogroup rules
 * @{
 */

/**
 * Implements hook_rules_event_info().
 */
function commerce_cart_rules_event_info() {
  $events = array();

  $events['commerce_cart_product_prepare'] = array(
    'label' => t('Before adding a product to the cart'),
    'group' => t('Commerce Cart'),
    'variables' => commerce_cart_rules_event_variables(),
    'access callback' => 'commerce_order_rules_access',
  );

  $events['commerce_cart_product_add'] = array(
    'label' => t('After adding a product to the cart'),
    'group' => t('Commerce Cart'),
    'variables' => commerce_cart_rules_event_variables(TRUE),
    'access callback' => 'commerce_order_rules_access',
  );

  $events['commerce_cart_product_remove'] = array(
    'label' => t('After removing a product from the cart'),
    'group' => t('Commerce Cart'),
    'variables' => commerce_cart_rules_event_variables(TRUE),
    'access callback' => 'commerce_order_rules_access',
  );

  return $events;
}

/**
 * Returns a variables array for shopping cart events.
 *
 * @param $line_item
 *   Boolean indicating whether or not to include product line item variables.
 */
function commerce_cart_rules_event_variables($line_item = FALSE) {
  $variables = array(
    'commerce_order' => array(
      'type' => 'commerce_order',
      'label' => t('Shopping cart order'),
    ),
    'commerce_product' => array(
      'label' => t('Product'),
      'type' => 'commerce_product',
    ),
    'quantity' => array(
      'label' => t('Quantity'),
      'type' => 'integer',
    ),
  );

  if ($line_item) {
    $variables += array(
      'commerce_line_item' => array(
        'label' => t('Product line item'),
        'type' => 'commerce_line_item',
      ),
      'commerce_line_item_unchanged' => array(
        'label' => t('Unchanged product line item'),
        'type' => 'commerce_line_item',
        'skip save' => TRUE,
        'handler' => 'rules_events_entity_unchanged',
      ),
    );
  }

  return $variables;
}

/**
 * Implements hook_rules_condition_info().
 */
function commerce_cart_rules_condition_info() {
  $conditions = array();

  $conditions['commerce_order_is_cart'] = array(
    'label' => t('Order is a shopping cart'),
    'parameter' => array(
      'commerce_order' => array(
        'type' => 'commerce_order',
        'label' => t('Order'),
      ),
    ),
    'group' => t('Commerce Cart'),
    'callbacks' => array(
      'execute' => 'commerce_cart_rules_order_is_cart',
    ),
  );

  return $conditions;
}

/**
 * Rules condition: checks to see if the given order is in a cart status.
 */
function commerce_cart_rules_order_is_cart($order) {
  return commerce_cart_order_is_cart($order);
}

/**
 * Implements hook_rules_action_info().
 */
function commerce_cart_rules_action_info() {
  $actions = array();

  $actions['commerce_cart_empty'] = array(
    'label' => t('Remove all products from an order'),
    'parameter' => array(
      'commerce_order' => array(
        'type' => 'commerce_order',
        'label' => t('Order to empty'),
      ),
    ),
    'group' => t('Commerce Cart'),
    'callbacks' => array(
      'execute' => 'commerce_cart_rules_empty',
    ),
  );

  $actions['commerce_cart_add_to_cart_message'] = array(
    'label' => t('Display a translatable Add to Cart message'),
    'parameter' => array(
      'commerce_product' => array(
        'type' => 'commerce_product',
        'label' => t('Product added to the cart'),
      ),
    ),
    'group' => t('Commerce Cart'),
    'callbacks' => array(
      'execute' => 'commerce_cart_rules_add_to_cart_message',
    ),
  );

  $actions['commerce_cart_product_add_by_sku'] = array(
    'label' => t('Add a product to the cart'),
    'parameter' => array(
      'user' => array(
        'type' => 'user',
        'label' => t('User'),
        'description' => t('Specify the user whose shopping cart order the product will be added to, typically <em>site:current-user</em>.'),
      ),
      'sku' => array(
        'type' => 'text',
        'label' => t('Product SKU'),
        'description' => t('The SKU of the product to add to the cart.'),
      ),
      'quantity' => array(
        'type' => 'integer',
        'label' => t('Quantity'),
        'default value' => 1,
      ),
      'combine' => array(
        'type' => 'boolean',
        'label' => t('Combine similar items in the cart'),
        'description' => t('If checked, the product will be combined added to an existing similar product line item in the cart by incrementing its quantity.'),
        'default value' => TRUE,
      ),
    ),
    'group' => t('Commerce Cart'),
    'callbacks' => array(
      'execute' => 'commerce_cart_rules_product_add_by_sku',
    ),
    'provides' => array(
      'product_add_line_item' => array(
        'type' => 'commerce_line_item',
        'label' => t('Added product line item'),
      ),
    ),
  );

  return $actions;
}

/**
 * Rules action: empties a cart order.
 */
function commerce_cart_rules_empty($order) {
  commerce_cart_order_empty($order);
}

/**
 * Rules action: displays a the default translatable Add to Cart message.
 */
function commerce_cart_rules_add_to_cart_message($product) {
  drupal_set_message(t('%title added to <a href="!cart-url">your cart</a>.', array('%title' => $product->title, '!cart-url' => url('cart'))));
}

/**
 * Rules action: adds a product to a user's shopping cart order.
 */
function commerce_cart_rules_product_add_by_sku($user, $sku, $quantity = 1, $combine = TRUE) {
  // Ensure we have a valid product SKU.
  if ($product = commerce_product_load_by_sku(trim($sku))) {
    // Pull the uid from the user passed in.
    if (empty($user) || empty($user->uid)) {
      $uid = NULL;
    }
    else {
      $uid = $user->uid;
    }

    // Only return an added line item.
    if ($line_item = commerce_cart_product_add_by_id($product->product_id, $quantity, $combine, $uid)) {
      return array('product_add_line_item' => $line_item);
    }
  }
}

/**
 * @}
 */
