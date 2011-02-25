<?php

/**
 * @file
 * Default rule configurations for Cart.
 */

/**
 * Implements hook_default_rules_configuration().
 */
function commerce_cart_default_rules_configuration() {
  $rules = array();

  // Add a reaction rule to update a shopping cart order's status to "Shopping
  // cart" when a product is added to or removed from the order.
  $rule = rules_reaction_rule();

  $rule->label = t('Reset the cart order status on product add or remove');
  $rule->active = TRUE;

  $rule
    ->event('commerce_cart_product_add')
    ->event('commerce_cart_product_remove')
    ->action('commerce_order_update_status', array(
      'order:select' => 'order',
      'order_status' => 'cart',
    ));

  $rules['commerce_cart_order_status_reset'] = $rule;

  return $rules;
}
