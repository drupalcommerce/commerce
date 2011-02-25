<?php

/**
 * @file
 * Hooks provided by the Price module.
 */


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
function hook_commerce_price_formatted_components(&$components, $price, $entity) {
  // No example.
}
