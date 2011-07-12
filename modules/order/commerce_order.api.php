<?php

/**
 * @file
 * Hooks provided by the Order module.
 */


/**
 * Allows you to prepare order data before it is saved.
 *
 * @param $order
 *   The order object to be saved.
 *
 * @see rules_invoke_all()
 */
function hook_commerce_order_presave($order) {
  // No example.
}
