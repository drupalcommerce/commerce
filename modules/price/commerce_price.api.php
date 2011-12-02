<?php

/**
 * @file
 * Hooks provided by the Price module.
 */


/**
 * Defines options for the calculation setting of a price field display formatter.
 *
 * @param $field
 *   The field info array the display formatter is for.
 * @param $instance
 *   The instance info array the display formatter is for.
 * @param $view_mode
 *   The view mode whose display formatter settings should be used on render.
 *
 * @return
 *   An array of key / value pairs for use in a select list options array.
 */
function hook_commerce_price_field_calculation_options($field, $instance, $view_mode) {
  // This example from the Product Pricing module adds an option for the display
  // formatter to specify the calculated sell price for use in the display.

  // If this is a single value purchase price field attached to a product...
  if (($instance['entity_type'] == 'commerce_product' || $field['entity_types'] == array('commerce_product')) &&
    $field['field_name'] == 'commerce_price' && $field['cardinality'] == 1) {
    return array('calculated_sell_price' => t('Display the calculated sell price for the current user.'));
  }
}

/**
 * Defines price component types for use in price component arrays.
 *
 * The price field data array includes a components array that keeps track of
 * the various components of a price that result in the price field's current
 * amount. A price field's amount column reflects the sum of all of its
 * components. Each component includes a component type and a price array
 * representing the amount, currency code, and data of the component.
 *
 * The Price module defines three default price component types:
 * - Base price: generally used to represent a product's base price as derived
 *   from the product itself and manipulated by Rules; appears in price
 *   component lists as the Subtotal
 * - Discount: used for generic discounts applied by Rules
 * - Fee: used for generic fees applied by Rules
 *
 * The Tax module also defines a price component type for each tax rate that
 * requests it.
 *
 * The price component type array structure includes the following keys:
 * - name: the machine-name of the price component type
 * - title: the translatable title of the price component for use in
 *   administrative displays
 * - display_title: the translatable display title of the price component for
 *   use in front end display; defaults to the title
 * - weight: the sort order of the price component type for use in listings of
 *   combined price components contained in a price's components array
 *
 * @return
 *   An array of price component type arrays keyed by name.
 */
function hook_commerce_price_component_type_info() {
    return array(
    'base_price' => array(
      'title' => t('Base price'),
      'display_title' => t('Subtotal'),
      'weight' => -50,
    ),
    'discount' => array(
      'title' => t('Discount'),
      'weight' => -10,
    ),
    'fee' => array(
      'title' => t('Fee'),
      'weight' => -20,
    ),
  );
}

/**
 * Allows modules to alter the price component types defined by other modules.
 *
 * @param $component_types
 *   The array of price component types defined by enabled modules.
 */
function hook_commerce_price_component_type_info_alter(&$component_types) {
  // No example.
}

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
