<?php
// $Id$

/**
 * @file
 * Hooks provided by the Product module.
 */


/**
 * Allows you to prepare product data before it is saved.
 *
 * @param $product
 *   The product object to be saved.
 *
 * @see rules_invoke_all()
 */
function hook_commerce_product_presave(&$product) {
  // No example.
}
