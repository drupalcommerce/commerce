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
