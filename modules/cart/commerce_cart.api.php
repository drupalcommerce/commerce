<?php

/**
 * @file
 * Hooks provided by the Cart module.
 */


/**
 * Determines whether or not the given order is a shopping cart order.
 *
 * When determining if an order should be considered a shopping cart order, the
 * Cart module provides a simple order status comparison but allows other
 * modules to make the decision based on some other criteria. Any module can
 * invalidate the cart status of an order by returning FALSE from this hook, but
 * a module can also opt to treat an order in a non-cart status as a cart by
 * receiving the second argument by reference and setting it to TRUE. It should
 * just be noted that this value could be returned to FALSE by some other
 * module implementing the same hook.
 *
 * @param $order
 *   The order whose cart status is being determined.
 * @param $is_cart
 *   Boolean indicating whether or not the order should be considered a cart
 *   order; initialized based on the order status.
 *
 * @return
 *   FALSE to indicate that an order should not be treated as a cart.
 *
 * @see commerce_cart_order_is_cart()
 */
function hook_commerce_cart_order_is_cart($order, $is_cart) {
  // No example.
}

/**
 * Allows modules to perform additional processing to convert an anonymous
 *   shopping cart order to an authenticated cart.
 *
 * When anonymous users login to the site, if they have shopping cart orders,
 * those are converted to authenticated shopping carts. This means their uid and
 * mail properties are updated along with the uid of any referenced customer
 * profiles. Additional modules can implement their own logic via this hook,
 * such as canceling any existing shopping cart orders the user might already
 * have prior to conversion of the anonymous cart.
 *
 * Modules that implement this hook do not need to save changes to the order, as
 * the Cart module will save the order after invoking the hook.
 *
 * @param $order_wrapper
 *   The entity metadata wrapper for the order being refreshed.
 * @param $account
 *   The user account the order will belong to.
 *
 * @see commerce_cart_order_convert()
 */
function hook_commerce_cart_order_convert($order_wrapper, $account) {
  // No example.
}

/**
 * Allows modules to perform additional processing to refresh a shopping cart
 *   order's contents.
 *
 * When an order is loaded, if it is in a shopping cart order status, its
 * contents are refreshed to get the current product prices. This prevents users
 * from checking out orders with stale contents. The API function
 * commerce_cart_order_refresh() takes care of product line item updates, but
 * this hook can be used for any additional updates.
 *
 * Modules that implement this hook do not need to save changes to the order, as
 * the Cart module will save the order after invoking the hook.
 *
 * @param $order_wrapper
 *   The entity metadata wrapper for the order being refreshed.
 *
 * @see commerce_cart_order_refresh()
 */
function hook_commerce_cart_order_refresh($order_wrapper) {
  // No example.
}

/**
 * Allows modules to perform additional processing when emptying a shopping cart
 * order.
 *
 * Modules that implement this hook do not need to save changes to the order, as
 * the Cart module will save the order after invoking the hook.
 *
 * @param $order
 *   The order being emptied.
 *
 * @see commerce_cart_order_empty()
 */
function hook_commerce_cart_order_empty($order) {
  // No example.
}

/**
 * Allows modules to add arbitrary AJAX commands to the array returned from the
 * Add to Cart form attributes refresh.
 *
 * When a product selection widget's value is changed, whether it is a product
 * select list or a product attribute field widget, the Add to Cart form gets
 * an AJAX refresh. The form will be rebuilt using the new form state and the
 * AJAX callback of the element that was changed will be called. For this form
 * it is commerce_cart_add_to_cart_form_attributes_refresh().
 *
 * The cart form's particular AJAX refresh function returns an array of AJAX
 * commands that perform HTML replacement on the page. However, other modules
 * may want to interact with the refreshed form. They can use this hook to
 * add additional items to the commands array, which is passed to the hook by
 * reference. Note that the form array and form state cannot be altered, just
 * the array of commands.
 *
 * @param &$commands
 *   The array of AJAX commands used to refresh the cart form with updated form
 *   elements and to replace product fields rendered on the page to match the
 *   currently selected product.
 * @param $form
 *   The rebuilt form array.
 * @param $form_state
 *   The form state array from the form.
 *
 * @see commerce_cart_add_to_cart_form_attributes_refresh()
 */
function hook_commerce_cart_attributes_refresh_alter(&$commands, $form, $form_state) {
  // Display an alert message showing the new default product ID.
  $commands[] = ajax_command_alert(t('Now defaulted to product @product_id.', array('@product_id' => $form['product_id']['#value'])));
}

/**
 * Allows modules to add additional property names to an array of comparison
 * properties used to determine whether or not a product line item can be
 * combined into an existing line item when added to the cart.
 *
 * @param &$comparison_properties
 *   The array of property names (including field names) that map to properties
 *   on the line item wrappers being compared to check for combination.
 */
function hook_commerce_cart_product_comparison_properties_alter(&$comparison_properties) {
  // Force separate line items when the same product is added to the cart from
  // different display paths.
  $comparison_properties[] = 'commerce_display_path';
}
