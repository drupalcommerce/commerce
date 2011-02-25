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
