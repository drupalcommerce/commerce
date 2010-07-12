<?php
// $Id$

/**
 * @file
 * Default rules configuration for Payment.
 */

/**
 * Implements hook_default_rules_configuration().
 */
function commerce_payment_default_rules_configuration() {
  $rules = array();

  // Add a default rule for each available payment method.
  foreach (commerce_payment_methods() as $method) {
    $rule = rules_reaction_rule();
    $rule->label = $method['title'];
    $rule->active = FALSE;
    $rule
      ->event('commerce_payment_methods')
      ->action('commerce_payment_enable_' . $method['method_id'], array(
        'commerce_order:select' => 'order',
      ));

    $rules['commerce_payment_' . $method['method_id']] = $rule;
  }
  return $rules;
}
