<?php

/**
 * @file
 * Hooks provided by the Cart module.
 */


/**
 * Allows modules to return a shopping cart order ID for a user before the Cart
 * module determines it using its default queries.
 *
 * Implementations of this hook are executed one at a time, meaning the first
 * implementation to return a non-NULL value will determine the current cart
 * order ID for the given user. Acceptable values will be either FALSE to
 * indicate that the user should not be considered to have a valid cart order or
 * an order ID to use besides the ID that would be returned by the default
 * queries in the Cart module.
 *
 * @param $uid
 *   The uid of the user whose shopping cart order ID should be returned.
 *
 * @return
 * The order ID (if a valid cart was found), FALSE (if the user should have no
 * current cart), or NULL (if the implementation cannot tell if the user has a
 * cart or not).
 */
function hook_commerce_cart_order_id($uid) {
  // No example.
}

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
 * @deprecated since 7.x-1.2, use hook_commerce_cart_order_is_cart_alter() instead.
 * @see commerce_cart_order_is_cart()
 * @see hook_commerce_cart_order_is_cart_alter()
 */
function hook_commerce_cart_order_is_cart($order, &$is_cart) {
  // No example.
}

/**
 * Alter the cart status of an order.
 *
 * When determining if an order should be considered a shopping cart order, the
 * Cart module provides a simple order status comparison but allows other
 * modules to make the decision based on some other criteria.
 *
 * @param $is_cart
 *   Boolean indicating whether or not the order should be considered a cart
 *   order; initialized based on the order status.
 * @param $order
 *   The order whose cart status is being determined.
 */
function hook_commerce_cart_order_is_cart_alter(&$is_cart, $order) {
  // No example.
}

/**
 * Allows modules to perform additional processing to convert an anonymous
 * shopping cart order to an authenticated cart.
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
 * Allows modules to perform processing on a shopping cart order prior to the
 * logic in commerce_cart_order_refresh() taking place.
 *
 * @param $order_wrapper
 *   The entity metadata wrapper for the order about to be refreshed.
 *
 * @see commerce_cart_order_refresh()
 * @see entity_metadata_wrapper()
 */
function hook_commerce_cart_order_pre_refresh($order_wrapper) {
  // No example.
}

/**
 * Allows modules to perform additional processing to refresh an individual line
 * item on a shopping cart order.
 *
 * Prior to this hook being invoked, product line items will have already had
 * their sell prices refreshed via the creation of a new line item for the same
 * product being passed through Rules for calculation.
 *
 * @param $line_item
 *   A line item object that should be updated as necessary for the refresh.
 * @param $order_wrapper
 *   An EntityMetadataWrapper for the order the line item is attached to.
 *
 * @see commerce_cart_order_refresh()
 */
function hook_commerce_cart_line_item_refresh($line_item, $order_wrapper) {
  // No example.
}

/**
 * Allows modules to perform additional processing to refresh a shopping cart
 * order's contents.
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
 * @see entity_metadata_wrapper()
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
 * @param $line_item
 *   A clone of the line item being added to the cart. Since this is a clone,
 *   changes made to it will not propagate up to the Add to Cart process.
 */
function hook_commerce_cart_product_comparison_properties_alter(&$comparison_properties) {
  // Force separate line items when the same product is added to the cart from
  // different display paths.
  $comparison_properties[] = 'commerce_display_path';
}

/**
 * Rules event hook: allows modules to operate prior to adding a product to the
 * cart but does not actually allow you to interrupt the process.
 *
 * Invoking this Rules event / hook does not result in the processing of any
 * return value, so it is not useful for interrupting a cart product add
 * operation outside of a redirect.
 *
 * @param $order
 *   The cart order object the product will be added to.
 * @param $product
 *   The product being added to the cart.
 * @param $quantity
 *   The quantity of the product to add to the cart.
 */
function hook_commerce_cart_product_prepare($order, $product, $quantity) {
  // No example.
}

/**
 * Rules event hook: allows modules to react to the addition of a product to a
 * shopping cart order.
 *
 * @param $order
 *   The cart order object the product was added to.
 * @param $product
 *   The product that was added to the cart.
 * @param $quantity
 *   The quantity of the product added to the cart.
 * @param $line_item
 *   The new or updated line item representing that product on the given order.
 */
function hook_commerce_cart_product_add($order, $product, $quantity, $line_item) {
  // No example.
}

/**
 * Rules event hook: allows modules to react to the removal of a product from a
 * shopping cart order.
 *
 * @param $order
 *   The cart order object the product was removed from.
 * @param $product
 *   The product that was removed from the cart.
 * @param $quantity
 *   The quantity of the product line item removed from the cart.
 * @param $line_item
 *   The product line item that was deleted to remove the product from the cart.
 */
function hook_commerce_cart_product_remove($order, $product, $quantity, $line_item) {
  // No example.
}
