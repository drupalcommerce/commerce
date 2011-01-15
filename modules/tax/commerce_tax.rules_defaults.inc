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
      // Load the tax type data for the curent rate.
      $tax_type = commerce_tax_type_load($tax_rate->type);

      // Check whether or not taxes of this type require the order context to
      // determine the applicability of the tax rate.
      $order_context = $tax_type ? $tax_type->order_context : FALSE;

      // Create a new rules component using variables based on the tax type.
      $rule = rule(commerce_tax_rate_component_variables($order_context));

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
