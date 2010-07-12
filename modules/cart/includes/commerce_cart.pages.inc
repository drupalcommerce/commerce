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
	// empty shopping cart page.
  if (empty($order)) {
    drupal_set_message(t('Add some items to your cart and then try checking out.'));
    $redirect_location = variable_get('commerce_checkout_empty_cart_redirect', '<front>');
    drupal_goto($redirect_location);
  }

  drupal_goto('checkout/' . $order->order_id);
}
