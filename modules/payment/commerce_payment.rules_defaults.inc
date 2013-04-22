<?php

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
  foreach (commerce_payment_methods() as $method_id => $payment_method) {
    $rule = rules_reaction_rule();

    $rule->label = $payment_method['title'];
    $rule->tags = array('Commerce Payment');
    $rule->active = $payment_method['active'];

    $rule
      ->event('commerce_payment_methods')
      ->action('commerce_payment_enable_' . $method_id, array(
        'commerce_order:select' => 'commerce-order',
        'payment_method' => $method_id,
      ));

    $rules['commerce_payment_' . $method_id] = $rule;
  }

  return $rules;
}
