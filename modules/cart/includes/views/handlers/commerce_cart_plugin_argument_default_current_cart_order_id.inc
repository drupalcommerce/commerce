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
    return commerce_cart_order_id($user->uid);
  }
}
