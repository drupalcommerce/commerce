<?php
// $Id$

/**
 * @file
 * Defines default tax components and rules.
 */

/**
 * Implements hook_default_rules_configuration().
 */
function commerce_tax_default_rules_configuration() {
  $rules = array();

  // Loop over every tax rate looking for rates requiring a default component.
  foreach (commerce_tax_rates() as $name => $tax_rate) {
    if ($tax_rate->component) {
      // Create a new rules component.
      $rule = rule(commerce_tax_rate_component_variables($tax_rate->order_context));

      $rule->label = t('Calculate @title', array('@title' => $tax_rate->title));

      // Add the action to apply the current tax.
      $rule
        ->action('commerce_tax_rate_apply', array(
          'commerce_order:select' => 'order',
          'tax_rate_name' => $name,
        ));

      $rules['commerce_tax_rate_' . $name] = $rule;
    }
  }

  return $rules;
}

/**
 * Returns an array of variables for use in tax rate components.
 *
 * @param $order
 *   Boolean indicating whether or not to include an order variable.
 */
function commerce_tax_rate_component_variables($order = FALSE) {
  $variables = array(
    'line_item' => array(
      'type' => 'commerce_line_item',
      'label' => t('Line item'),
    ),
  );

  if ($order) {
    $variables['order'] = array(
      'type' => 'commerce_order',
      'label' => t('Order', array()),
    );
  }

  return $variables;
}
