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

  // Add a reaction rule to display the default Add to Cart message.
  $rule = rules_reaction_rule();

  $rule->label = t('Display an Add to Cart message');
  $rule->active = TRUE;

  $rule
    ->event('commerce_cart_product_add')
    ->action('commerce_cart_add_to_cart_message', array(
      'commerce_product:select' => 'commerce-product',
    ));

  $rules['commerce_cart_add_to_cart_message'] = $rule;

  // Add a reaction rule to update a shopping cart order's status to "Shopping
  // cart" when a product is added to or removed from the order.
  $rule = rules_reaction_rule();

  $rule->label = t('Reset the cart order status on product add or remove');
  $rule->active = TRUE;

  $rule
    ->event('commerce_cart_product_add')
    ->event('commerce_cart_product_remove')
    ->action('commerce_order_update_status', array(
      'commerce_order:select' => 'commerce-order',
      'order_status' => 'cart',
    ));

  $rules['commerce_cart_order_status_reset'] = $rule;

  // Add a reaction rule to unset the price of disabled products in the cart
  // during price calculation, effectively removing them from the order.
  $rule = rules_reaction_rule();

  $rule->label = t('Unset the price of disabled products in the cart');
  $rule->active = TRUE;
  $rule->weight = 10;

  $rule
    ->event('commerce_product_calculate_sell_price')
    ->condition(rules_condition('data_is_empty', array(
      'data:select' => 'commerce-line-item:line-item-id',
    ))->negate())
    ->condition('entity_has_field', array(
      'entity:select' => 'commerce-line-item',
      'field' => 'commerce_product',
    ))
    ->condition('data_is', array(
      'data:select' => 'commerce-line-item:commerce-product:status',
      'op' => '==',
      'value' => '0',
    ))
    ->action('data_set', array(
      'data:select' => 'commerce-line-item:commerce-unit-price:amount',
    ));

  $rules['commerce_cart_unset_disabled_products'] = $rule;

  return $rules;
}
