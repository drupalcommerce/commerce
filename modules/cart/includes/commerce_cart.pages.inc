<?php
// $Id$

/**
 * @file
 * The page and form callbacks for use by the shopping cart.
 */


/**
 * Redirects invalid checkout attempts or displays the checkout form if valid.
 */
function commerce_cart_checkout_router() {
  global $user;

  // Load the shopping cart order.
  $order = commerce_cart_order_load($user->uid);

  // If no shopping cart order could be found, redirect away from checkout.
  // TODO: Redirect to the cart page instead which would then appear as an
  // empty shopping cart page. Depends on
  if (empty($order) || commerce_line_items_quantity($order->line_items, 'product') == 0) {
    drupal_set_message(t('Add some items to your cart and then try checking out.'));
    drupal_goto(variable_get('commerce_checkout_empty_redirect', ''));
  }

  drupal_goto('checkout/' . $order->order_id);
}
