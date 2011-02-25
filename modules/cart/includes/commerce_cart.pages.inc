<?php

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
  $wrapper = entity_metadata_wrapper('commerce_order', $order);

  // If no shopping cart order could be found, redirect away from checkout.
  // TODO: Redirect to the cart page instead which would then appear as an
  // empty shopping cart page.
  if (empty($order) || commerce_line_items_quantity($wrapper->commerce_line_items, 'product') == 0) {
    drupal_set_message(t('Add some items to your cart and then try checking out.'));
    drupal_goto(variable_get('commerce_checkout_empty_redirect', ''));
  }

  drupal_goto('checkout/' . $order->order_id);
}

/**
 * Displays the shopping cart form and associated information.
 */
function commerce_cart_view() {
  global $user;

  // First check for items in the shopping cart.
  $order = commerce_cart_order_load($user->uid);

  // If the cart is empty...
  if (!$order || empty($order->commerce_line_items)) {
    // Display an appropriate message.
    $content = theme('commerce_cart_empty_page');
  }
  else {
    drupal_add_css(drupal_get_path('module', 'commerce_cart') . '/theme/commerce_cart.css');

    // Add the form for editing the cart contents.
    $content = commerce_embed_view('commerce_cart_form', 'default', array($order->order_id));
  }

  return $content;
}
