<?php
// $Id$

/**
 * @file
 * Default rules configuration for Order.
 */

/**
 * Implements hook_default_rules_configuration().
 */
function commerce_order_default_rules_configuration() {
  $rules = array();

  // Add a rule configuration to update an order to the default status of the
  // pending order status upon checkout completion.
  $order_state = commerce_order_state_load('pending');

  $rule = rules_reaction_rule();

  $rule->label = t('Update the order status on checkout completion');
  $rule->active = TRUE;

  $rule
    ->event('commerce_checkout_complete')
    ->action('commerce_order_update_status', array(
      'commerce_order:select' => 'order',
      'order_status' => $order_state->default_status,
    ));

  $rules['commerce_checkout_order_status_update'] = $rule;

  return $rules;
}
