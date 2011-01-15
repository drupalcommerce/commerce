<?php
// $Id$

/**
 * @file
 * Documents hooks provided by the Tax module.
 */


/**
 * Defines tax types used to categorize tax rates.
 *
 * @return
 *   An array of information about tax types available for use by rates. The
 *   returned array should be an associative array of tax type arrays keyed by
 *   the tax type name. Each tax type array can include the following keys:
 *   - title: the display title of the tax type
 *   - description: a short description of the tax type
 *   - display_inclusive: boolean indicating whether or not prices containing
 *       this tax will include the tax amount in the displayed price; defaults
 *       to FALSE
 *   - order_context: boolean indicating whether or not tax rates of this type
 *       require an order context to be calculated (i.e. sales tax based on the
 *       shipping location); defaults to FALSE
 */
function hook_commerce_tax_type_info() {
  $tax_types = array();

  $tax_types['sales_tax'] = array(
    'title' => t('Sales tax'),
    'display_inclusive' => FALSE,
  );

  return $tax_types;
}

/**
 * Alter tax types.
 *
 * @see hook_commerce_tax_type_info()
 */
function hook_commerce_tax_type_info_alter(&$tax_types) {
  $tax_types['sales_tax']['display_inclusive'] = TRUE;
}

/**
 * Defines tax rates that may be applied to line items.
 *
 * @return
 *   An array of information about available tax rates. The returned array
 *   should be an associative array of tax rate arrays keyed by the tax rate
 *   name. Each tax rate array can include the following keys:
 *   - title: the display title of the tax rate
 *   - description: a short description of the tax rate
 *   - rate: the percentage used to calculate this tax expressed as a decimal
 *   - type: the name of the tax type this rate belongs to
 *   - component: boolean indicating whether or not this rate will get its own
 *       default Rules component used for determining the applicability of the
 *       tax to a line item; defaults to TRUE
 */
function hook_commerce_tax_rate_info() {
  $tax_rates = array();

  $tax_rates['ky_sales_tax'] = array(
    'title' => t('KY sales tax'),
    'rate' => .06,
    'type' => 'sales_tax',
  );

  return $tax_rates;
}

/**
 * Alter tax rates.
 *
 * @see hook_commerce_tax_rate_info()
 */
function hook_commerce_tax_rate_info_alter(&$tax_rates) {
  $tax_rates['ky_sales_tax']['rate'] = .0625;
}
