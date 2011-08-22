<?php

/**
 * @file
 * Hooks provided by the Price module.
 */


/**
 * Functions as a secondary hook_field_formatter_prepare_view() for price fields,
 * allowing modules to alter prices prior to display.
 *
 * This hook is used by modules like the Product Pricing module that implement
 * ways to alter prices prior to display. Modules implementing this hook are
 * currently responsible to make sure they do not alter price data twice on the
 * same pageload.
 */
function hook_commerce_price_field_formatter_prepare_view($entity_type, $entities, $field, $instances, $langcode, $items, $displays) {
  static $calculated_prices = array();

  // If this is a single value purchase price field attached to a product...
  if ($entity_type == 'commerce_product' && $field['field_name'] == 'commerce_price' && $field['cardinality'] == 1) {
    // Prepare the items for each entity passed in.
    foreach ($entities as $product_id => $product) {
      // If this price should be calculated and hasn't been already...
      if (!empty($displays[$product_id]['settings']['calculation']) &&
        $displays[$product_id]['settings']['calculation'] == 'calculated_sell_price' &&
        empty($calculated_prices[$product_id][$field['field_name']])) {
        // Replace the data being displayed with data from a calculated price.
        $items[$product_id] = array(commerce_product_calculate_sell_price($product));

        // Keep track of which prices have already been calculated.
        $calculated_prices[$product_id][$field['field_name']] = TRUE;
      }
    }
  }
}

/**
 * Lets modules alter price components prior to display through the "Formatted
 *   amount with components" display formatter.
 *
 * @param &$components
 *   The array of totaled price components.
 * @param $price
 *   The price array the components came from.
 * @param $entity
 *   The entity the price belongs to.
 *
 * @see commerce_price_field_formatter_view()
 */
function hook_commerce_price_formatted_components_alter(&$components, $price, $entity) {
  // No example.
}
