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
    'order' => array(
      'type' => 'commerce_order',
      'label' => t('Shopping cart order'),
    ),
    'product' => array(
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
      'line_item' => array(
        'label' => t('Product line item'),
        'type' => 'commerce_line_item',
      ),
      'line_item_unchanged' => array(
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
      'order' => array(
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
      'order' => array(
        'type' => 'commerce_order',
        'label' => t('Order to empty'),
      ),
    ),
    'group' => t('Commerce Cart'),
    'callbacks' => array(
      'execute' => 'commerce_cart_rules_empty',
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
 * @}
 */
