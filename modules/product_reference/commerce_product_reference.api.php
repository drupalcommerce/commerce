<?php

/**
 * @file
 * Hooks provided by the Product Reference module.
 */

/**
 * Allows modules to alter the delta value used to determine the default product
 * entity in an array of referenced products.
 *
 * The basic behavior for determining a default product from an array of
 * referenced products is to use the first referenced product. This hook allows
 * modules to change that to a different delta value.
 *
 * Note that in some cases $products will be keyed by product ID while in other
 * cases it will be 0 indexed.
 *
 * @param $delta
 *   The key in the $products array of the product that should be the default
 *   product for display purposes in a product reference field value array.
 * @param $products
 *   An array of product entities referenced by a product reference field.
 *
 * @see commerce_product_reference_default_product()
 */
function hook_commerce_product_reference_default_delta_alter(&$delta, $products) {
  // If a product with the SKU PROD-01 exists in the array, set that as the
  // default regardless of its position.
  foreach ($products as $key => $product) {
    if ($product->sku == 'PROD-01') {
      $delta = $key;
    }
  }
}
