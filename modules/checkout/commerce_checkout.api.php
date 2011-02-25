<?php

/**
 * @file
 * Hooks provided by the Checkout module.
 */


/**
 * Routes checkout/%commerce_order* to an alternate URL if necessary.
 *
 * The checkout module uses two URLs for the checkout form, displaying a form
 * specific to the checkout page indicated by the URL.
 *
 * - checkout/%commerce_order: used for the first checkout page
 * - checkout/%commerce_order/%commerce_checkout_page: used for all subsequent
 *   checkout pages
 *
 * The page callback for these URLs checks the user's access to the requested
 * checkout page for the given order and to make sure the order has line items.
 * After these two checks, it gives other modules an opportunity to evaluate the
 * order and checkout page to see if any other redirection is necessary. This
 * hook should not be used to alter the output at the actual checkout URL.
 *
 * @param $order
 *   The order object specified by the checkout URL.
 * @param $checkout_page
 *   The checkout page array specified by the checkout URL.
 *
 * @see commerce_checkout_router()
 */
function hook_commerce_checkout_router($order, $checkout_page) {
  global $user;

  // Redirect anonymous users to a custom login page instructing them to login
  // prior to checkout. (Note that Drupal Commerce does not require users to
  // login prior to checkout as an e-commerce best practice.)
  if (!$user->uid) {
    drupal_set_message(t('Please login or create an account now to continue checkout.'));
    drupal_goto('checkout/login/' . $order->order_id);
  }
}
