<?php

/**
 * @file
 * Hooks provided by the Product module.
 */


/**
 * Defines the types of products available for creation on the site.
 *
 * Product types are represented as bundles of the Commerce Product entity type.
 * Each one has a unique machine-name, title, description, and help text. They
 * can also each have unique fields to store additional product data that may
 * be exposed to the Add to Cart form as product attributes.
 *
 * The Product UI module implements this hook to define product types based on
 * information stored in the database. On installation, Product UI adds a basic
 * product type named "Product" to the database that can be used exclusively on
 * a site with simple products or deleted if unnecessary for a given site.
 *
 * The product type array structure includes the following keys:
 * - type: the machine-name of the product type
 * - name: the translatable name of the product type
 * - description: a translatable description of the product type for use in
 *   administrative lists and pages
 * - help: the translatable help text included at the top of the add / edit form
 *   for products of this type
 * - revision: for product types governed by the Product UI module, this boolean
 *   determines whether or not products of this type will default to creating
 *   new revisions when edited
 * - module: the name of the module defining the product type; should not be set
 *   by the hook itself but will be set when all product types are loaded
 *
 * @return
 *   An array of product type arrays keyed by type.
 */
function hook_commerce_product_type_info() {
  $product_types = array();

  $product_types['ebook'] = array(
    'type' => 'ebook',
    'name' => t('E-book'),
    'description' => t('An e-book product uploaded to the site as a PDF.'),
  );

  return $product_types;
}

/**
 * Allows modules to alter the product types defined by other modules.
 *
 * @param $product_types
 *   The array of product types defined by enabled modules.
 *
 * @see hook_commerce_product_type_info()
 */
function hook_commerce_product_type_info_alter(&$product_types) {
  // No example.
}

/**
 * Allows modules to react to the creation of a new product type via Product UI.
 *
 * @param $product_type
 *   The product type info array.
 * @param $skip_reset
 *   Boolean indicating whether or not this insert will trigger a cache reset
 *   and menu rebuild.
 *
 * @see commerce_product_ui_product_type_save()
 */
function hook_commerce_product_type_insert($product_type, $skip_reset) {
  // No example.
}

/**
 * Allows modules to react to the update of a product type via Product UI.
 *
 * @param $product_type
 *   The product type info array.
 * @param $skip_reset
 *   Boolean indicating whether or not this update will trigger a cache reset
 *   and menu rebuild.
 *
 * @see commerce_product_ui_product_type_save()
 */
function hook_commerce_product_type_update($product_type, $skip_reset) {
  // No example.
}

/**
 * Allows modules to react to the deletion of a product type via Product UI.
 *
 * @param $product_type
 *   The product type info array.
 * @param $skip_reset
 *   Boolean indicating whether or not this deletion will trigger a cache reset
 *   and menu rebuild.
 *
 * @see commerce_product_ui_product_type_delete()
 */
function hook_commerce_product_type_delete($product_type, $skip_reset) {
  // No example.
}

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
