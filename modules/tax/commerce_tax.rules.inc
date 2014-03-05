<?php

/**
 * @file
 * Rules integration for line items.
 *
 * @addtogroup rules
 * @{
 */


/**
 * Implements hook_rules_action_info().
 */
function commerce_tax_rules_action_info() {
  $actions = array();

  $actions['commerce_tax_remove_taxes'] = array(
    'label' => t('Remove taxes applied to a line item'),
    'parameter' => array(
      'commerce_line_item' => array(
        'type' => 'commerce_line_item',
        'label' => t('Line item'),
        'wrapped' => TRUE,
        'save' => TRUE,
        'restriction' => 'selector',
      ),
      'increase_base_price' => array(
        'type' => 'boolean',
        'label' => t('Increase the base price amount by the amount of display inclusive taxes removed.'),
        'description' => t('If left unchecked, a price of £2.95 including £0.49 VAT will be reduced to £2.46. If checked it will be set to £2.95 with no tax included.'),
        'default value' => FALSE,
        'restriction' => 'input',
      ),
      'tax_rates' => array(
        'type' => 'list<text>',
        'label' => t('Limit removal to only a certain set of tax rates'),
        'description' => t('If no tax rates are selected, all tax rates will be removed from the line item. Use ctrl + click (or command + click) to deselect a tax rate.'),
        'options list' => 'commerce_tax_rate_titles',
        'optional' => TRUE,
        'restriction' => 'input',
      ),
    ),
    'group' => t('Commerce Tax'),
  );

  if (count(commerce_tax_rates()) > 0) {
    $actions['commerce_tax_rate_apply'] = array(
      'label' => t('Apply a tax rate to a line item'),
      'parameter' => array(
        'commerce_line_item' => array(
          'type' => 'commerce_line_item',
          'label' => t('Line item'),
        ),
        'tax_rate_name' => array(
          'type' => 'text',
          'label' => t('Tax rate'),
          'options list' => 'commerce_tax_rate_titles',
        ),
      ),
      'provides' => array(
        'applied_tax' => array(
          'type' => 'commerce_price',
          'label' => t('Applied tax'),
        ),
      ),
      'group' => t('Commerce Tax'),
      'callbacks' => array(
        'execute' => 'commerce_tax_rate_rules_apply',
      ),
    );
  }

  if (count(commerce_tax_types()) > 0) {
    $actions['commerce_tax_calculate_by_type'] = array(
      'label' => t('Calculate taxes for a line item'),
      'parameter' => array(
        'commerce_line_item' => array(
          'type' => 'commerce_line_item',
          'label' => t('Line item'),
        ),
        'tax_type_name' => array(
          'type' => 'text',
          'label' => t('Tax type'),
          'options list' => 'commerce_tax_type_titles',
        ),
      ),
      'group' => t('Commerce Tax'),
    );
  }

  return $actions;
}

/**
 * Rules actions: removes all taxes applied to a line item.
 */
function commerce_tax_remove_taxes($line_item_wrapper, $increase_base_price, $tax_rates) {
  // Load all the tax component types to look for matching price components in
  // the line item's unit price. Filter the list by tax rates that should be
  // removed if only some were specified.
  $component_types = commerce_tax_commerce_price_component_type_info();

  if (!empty($tax_rates)) {
    foreach ($component_types as $name => $component_type) {
      if (!in_array($component_type['tax_rate'], $tax_rates)) {
        unset($component_types[$name]);
      }
    }
  }

  // Loop over the price components in the line item's unit price.
  $price = $line_item_wrapper->commerce_unit_price->value();
  $new_components = array();

  foreach ($price['data']['components'] as $key => $component) {
    // Look for components that match one of the defined tax price components.
    if (in_array($component['name'], array_keys($component_types))) {
      // Remove it from the components array.
      unset($price['data']['components'][$key]);

      // If the component was marked as "included" in the price amount, update
      // the price amount to reflect the difference.
      if (!empty($component['included'])) {
        $price['amount'] -= $component['price']['amount'];

        // If the user opted to increase the base price by the amount of the
        // display inclusive taxes removed, add them back as new price components.
        if (!empty($increase_base_price)) {
          $price['data']['components'][] = array(
            'name' => 'base_price',
            'price' => array(
              'amount' => $component['price']['amount'],
              'currency_code' => $component['price']['currency_code'],
              'data' => array(),
            ),
            'included' => TRUE,
          );

          $price['amount'] += $component['price']['amount'];
        }
      }
    }
  }

  $line_item_wrapper->commerce_unit_price = $price;
}

/**
 * Rules action: loads and applies a tax rate to the given line item.
 */
function commerce_tax_rate_rules_apply($line_item, $name) {
  if ($tax_rate = commerce_tax_rate_load($name)) {
    $tax_price = commerce_tax_rate_apply($tax_rate, $line_item);

    // If tax was applied, return the price array as a new variable for use in
    // subsequent actions.
    if ($tax_price) {
      return array('applied_tax' => $tax_price);
    }
  }
}

/**
 * Rules action: checks for the application of each tax rate of a certain type.
 */
function commerce_tax_calculate_by_type($line_item, $tax_type_name) {
  if ($tax_type = commerce_tax_type_load($tax_type_name)) {
    commerce_tax_type_calculate_rates($tax_type, $line_item);
  }
}

/**
 * @}
 */
