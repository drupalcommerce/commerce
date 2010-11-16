<?php
// $Id$

/**
 * @file
 * Hooks provided by the Product module.
 */


/**
 * Lets modules specify the path information expected by a uri callback.
 *
 * The Product module defines a uri callback for the product entity even though
 * it doesn't actually define any product menu items. The callback invokes this
 * hook and will return the first set of path information it finds. If the
 * Product UI module is enabled, it will alter the product entity definition to
 * use its own uri callback that checks commerce_product_uri() for a return
 * value and defaults to an administrative link defined by that module.
 *
 * This hook is used as demonstrated below by the Product Reference module to
 * direct modules to link the product to the page where it is actually displayed
 * to the user. Currently this is specific to nodes, but the system should be
 * beefed up to accommodate even non-entity paths.
 *
 * @param $product
 *   The product object whose uri information should be returned.
 *
 * @return
 *   Implementations of this hook should return an array of information as
 *   expected to be returned to entity_uri() by a uri callback function.
 *
 * @see commerce_product_uri()
 * @see entity_uri()
 */
function hook_commerce_product_uri($product) {
  // If the product has a display context, use it entity_uri().
  if (!empty($product->display_context)) {
    return entity_uri($product->display_context['entity_type'], $product->display_context['entity']);
  }
}

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
