<?php

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
 * Lets modules prevent the deletion of a particular product.
 *
 * Before a product can be deleted, other modules are given the chance to say
 * whether or not the action should be allowed. Modules implementing this hook
 * can check for reference data or any other reason to prevent a product from
 * being deleted and return FALSE to prevent the action.
 *
 * This is an API level hook, so implementations should not display any messages
 * to the user (although logging to the watchdog is fine).
 *
 * @param $product
 *   The product to be deleted.
 *
 * @return
 *   TRUE or FALSE indicating whether or not the given product can be deleted.
 *
 * @see commerce_product_reference_commerce_product_can_delete()
 */
function hook_commerce_product_can_delete($product) {
  // Use EntityFieldQuery to look for line items referencing this product and do
  // not allow the delete to occur if one exists.
  $query = new EntityFieldQuery();

  $query
    ->entityCondition('entity_type', 'commerce_line_item', '=')
    ->entityCondition('bundle', 'product', '=')
    ->fieldCondition('product', 'product_id', $product->product_id, '=')
    ->count();

  return $query->execute() > 0 ? FALSE : TRUE;
}
