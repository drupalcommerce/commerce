<?php

/**
 * @file
 * Contains a default argument plugin to return the current user's cart order ID.
 */

/**
 * Default argument plugin to get the current user's cart order ID.
 */
class commerce_cart_plugin_argument_default_current_cart_order_id extends views_plugin_argument_default {
  function get_argument() {
    global $user;

    $order_id = commerce_cart_order_id($user->uid);

    // Return an explicit 0 if not found instead of FALSE or NULL, as the
    // argument provided will be used to attempt a commerce_order_load() that
    // must accept an integer as the order ID.
    if (empty($order_id)) {
      return 0;
    }

    return $order_id;
  }
}
