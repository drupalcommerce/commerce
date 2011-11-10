<?php

/**
 * @file
 * Defines default tax components and rules.
 */

/**
 * Implements hook_default_rules_configuration().
 */
function commerce_tax_default_rules_configuration() {
  $rules = array();

  // Loop over every tax rate looking for rates requiring a default component
  // that specify a a component name.
  foreach (commerce_tax_rates() as $name => $tax_rate) {
    if ($tax_rate['default_rules_component'] && !empty($tax_rate['rules_component'])) {
      // Define a default rules component for applying this tax rate to a line
      // item using the name specified by the rate.
      $rule = rule(commerce_tax_rate_component_variables());
      $rule->label = t('Calculate @title', array('@title' => $tax_rate['title']));

      // Add the action to apply the current tax.
      $rule
        ->action('commerce_tax_rate_apply', array(
          'commerce_line_item:select' => 'commerce-line-item',
          'tax_rate_name' => $name,
        ));

      $rules[$tax_rate['rules_component']] = $rule;
    }
  }

  // Loop over every tax type and define a product pricing rule to calculate
  // taxes of that type if it specifies a rule name.
  foreach (commerce_tax_types() as $name => $tax_type) {
    if (!empty($tax_type['rule'])) {
      // Create a new product pricing rule.
      $rule = rules_reaction_rule();

      $rule->label = t('Calculate taxes: @title', array('@title' => $tax_type['title']));
      $rule->active = TRUE;

      // Add the action to invoke every tax rate's component matching this type.
      $rule
        ->event('commerce_product_calculate_sell_price')
        ->action('commerce_tax_calculate_by_type', array(
            'commerce_line_item:select' => 'commerce-line-item',
            'tax_type_name' => $name,
          ));

      $rules[$tax_type['rule']] = $rule;
    }
  }

  return $rules;
}

/**
 * Returns an array of variables for use in tax rate components.
 */
function commerce_tax_rate_component_variables() {
  return array(
    'commerce_line_item' => array(
      'type' => 'commerce_line_item',
      'label' => t('Line item'),
    ),
  );
}
