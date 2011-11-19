<?php

/**
 * @file
 * Hooks provided by the Line Item module.
 */


/**
 * Defines line item types that serve as bundles of the line item entity type.
 *
 * The Line Item module uses this hook to collect information on the line item
 * types enabled on the site. A line item is any aspect of an order that adds to
 * (or subtracts from) the order total. Each line item must be of one of the
 * defined line item types, which defines how it interacts with the shopping
 * cart, order edit interface, and other parts of Commerce.
 *
 * When modules are enabled that implement hook_commerce_line_item_type_info(),
 * the Line Item module will detect it and perform initial configuration of the
 * line item type by adding locked unit price and total price fields to the new
 * bundle. It then allows the module defining the line item type to perform any
 * additional configuration through the use of a special callback defined in the
 * line item type’s definition. Any additional fields required by line items of
 * this type can be added here, such as the product reference and display path
 * fields added to the core Product line item type.
 *
 * Core line item types include:
 * - Product: defined by the Product Reference module, this line item type
 *   references a product and uses the SKU and special view modes for display
 *   in line item interfaces.
 *
 * A single line item type array is referred to as $line_item_type.
 * An array of line item type arrays keyed is referred to as $line_item_types.
 * The type value of a line item type is referred to as $type.
 *
 * @return
 *   An array of line item type info arrays keyed by the type string. Line item
 *   type info arrays are associative arrays containing the following keys:
 *   - type: string containing the machine-name of the line item type; should
 *     only include lowercase letters, numbers, -, and _.
 *   - name: the translatable name of the line item type, used in administrative
 *     interfaces including the “Add line item” form on the order edit page.
 *   - description: a translatable description of the intended use of line items
 *     of this type.
 *   - product: boolean indicating whether or not this line item type functions
 *     as a product in various systems, such as the Add to Cart form. If set to
 *     TRUE, the line item type must also contain the fields added to the base
 *     product line item type, commerce_product and commerce_display_path. To
 *     achieve this the line item type can reuse the configuration callback
 *     of the Product line item type, commerce_product_line_item_configuration().
 *   - add_form_submit_value: the translatable value of the submit button used
 *     for adding line items of this type to an order.
 *   - base: string used as the base for the magically constructed callback
 *     names, each of which will be defaulted to [base]_[callback] unless
 *     explicitly set in the callbacks array; defaults to the type.
 *   - callbacks: an array of callback function names for the various types of
 *     callback required for all the line item type operations (arguments per
 *     callback in parentheses):
 *     - configuration($line_item_type): configures the line item type for use,
 *       typically by adding additional fields to the line item type.
 *     - title($line_item): returns a sanitized title of the line item for use
 *       in Views and other displays.
 *     - add_form($element, &$form_state): returns the form elements necessary
 *       to add a line item of this type to an order via a line item manager
 *       widget.
 *     - add_form_submit($line_item, $element, &$form_state, $form): processes
 *       the input from the "Add line item" form elements for this line item
 *       type, adding data to the new line item object; should return an error
 *       message if the line item should not be added for some reason.
 *
 * @see hook_commerce_line_item_type_info_alter()
 */
function hook_commerce_line_item_type_info() {
  $line_item_types = array();

  $line_item_types['product'] = array(
    'type' => 'product',
    'name' => t('Product'),
    'description' => t('References a product and displays it with the SKU as the label.'),
    'product' => TRUE,
    'add_form_submit_value' => t('Add product'),
    'base' => 'commerce_product_line_item',
  );

  return $line_item_types;
}

/**
 * Allows modules to alter the line item types info array.
 *
 * @param &$line_item_types
 *   An array of line item type info arrays keyed by type.
 */
function hook_commerce_line_item_type_info_alter(&$line_item_types) {
  // No example.
}


/**
 * Defines links for use in line item summary area handlers on Views.
 *
 * The line item summary area handler totals the value of the various line items
 * in a View and optionally includes a set of links. These are used in the core
 * shopping cart block View to let the user browse straight to the shopping cart
 * form or the checkout form. The format of the return value is a links array as
 * required by theme_links() with the addition of a weight parameter used to
 * sort the links prior to display.
 *
 * @return
 *   An associative array of link arrays keyed by link names, with the names
 *   being appended to the class of each link's list item when rendered by
 *   theme_links(). Link arrays should include the following key / value
 *   properties expected by theme_links():
 *   - title: the link text
 *   - href: the link URL; if ommitted, the link is rendered as plain text
 *   - html: boolean indicating whether or not the link text should be rendered
 *     as HTML or escaped; defaults to FALSE
 *   - weight: custom to this hook, the weight property is an integer value used
 *     to sort links prior to rendering; defaults to 0
 *   - access: custom to this hook, a boolean value indicating whether or not
 *     the current user has access to the link; defaults to TRUE
 *   The full link array will be passed to theme_link(), meaning any additional
 *   properties can be included as desired (such as the attributes array as
 *   demonstrated below).
 *
 * @see commerce_line_item_summary_links()
 * @see commerce_cart_commerce_line_item_summary_link_info()
 * @see theme_links()
 */
function hook_commerce_line_item_summary_link_info() {
  return array(
    'view_cart' => array(
      'title' => t('View cart'),
      'href' => 'cart',
      'attributes' => array('rel' => 'nofollow'),
      'weight' => 0,
    ),
    'checkout' => array(
      'title' => t('Checkout'),
      'href' => 'checkout',
      'attributes' => array('rel' => 'nofollow'),
      'weight' => 5,
      'access' => user_access('access checkout'),
    ),
  );
}

/**
 * Allows you to alter line item summary links.
 *
 * @param $links
 *   Array of line item summary links keyed by name exposed by
 *   hook_commerce_line_item_summary_link_info() implementations.
 *
 * @see hook_commerce_line_item_summary_link_info()
 */
function hook_commerce_line_item_summary_link_info_alter(&$links) {
  // Alter the weight of the checkout link to display before the view cart link.
  if (!empty($links['checkout'])) {
    $links['checkout']['weight'] = -5;
  }
}

/**
 * Allows you to add additional components to a line item's unit price when the
 * price is being rebased due to a manual adjustment.
 *
 * When a line item's unit price is adjusted via the line item manager widget,
 * its components need to be recalculated using the given price as the new base
 * price. Otherwise old component data will be used when calculating the total
 * of the order, causing it not to match with the actual line item total.
 *
 * The function that invokes this hook first sets the base price to the new unit
 * price amount and currency code and allows other modules to add additional
 * components to the new components array as required based on the components of
 * the price from before the edit.
 *
 * @param &$price
 *   A price array representing the new unit price. New components should be
 *   added to this price's data array.
 * @param $old_components
 *   The old unit price components array extracted from the line item before the
 *   hook is invoked. The unit price on the line item will already be reset at
 *   this time, so the components must be preserved in this array.
 * @param $line_item
 *   The line item object whose unit price is being rebased.
 *
 * @see commerce_line_item_rebase_unit_price()
 * @see commerce_price_component_add()
 */
function hook_commerce_line_item_rebase_unit_price(&$price, $old_components, $line_item) {
  // No example.
}
