<?php
// $Id$

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
