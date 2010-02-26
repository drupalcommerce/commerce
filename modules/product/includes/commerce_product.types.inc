<?php
// $Id$

/**
 * @file
 * The class for product types. This is not an entity still has a swappable
 * class.
 */

class CommerceProductTypes {

  protected $types = NULL;

  /**
   * Returns an initialized product type object.
   */
  public function create() {
    return (object) array(
      'type' => '',
      'name' => '',
      'description' => '',
      'help' => '',
    );
  }

  /**
   * Loads a product type.
   *
   * @param $type
   *   The machine-readable name of the product type.
   */
  public function load($type) {
    return db_query('SELECT * FROM {commerce_product_type} WHERE type = :type', array(':type' => $type))->fetchObject();
  }

  /**
   * Returns an array of product type objects keyed by type.
   */
  public function types() {
    // If it did not exist, fetch the types now.
    if (!isset($this->types)) {
      $this->types = db_query('SELECT * FROM {commerce_product_type}')->fetchAllAssoc('type');
    }

    return $this->types;
  }

  /**
   * Saves a product type.
   *
   * This function will either insert a new product type if $product_type->is_new
   * is set or attempt to update an existing product type if it is not. It does
   * not currently support changing the machine-readable name of the product type,
   * nor is this possible through the form supplied by the Product UI module.
   *
   * @param $product_type
   *   The product type object containing the basic properties as initialized in
   *     commerce_product_type_new().
   * @return
   *   The return value of the call to drupal_write_record() to save the product
   *     type, either FALSE on failure or SAVED_NEW or SAVED_UPDATED indicating
   *     the type of query performed to save the product type.
   */
  public function save($product_type) {
    $op = drupal_write_record('commerce_product_type', $product_type, empty($product_type->is_new) ? 'type' : NULL);
    menu_rebuild();
    // If this is a new product type and the insert did not fail...
    if (!empty($product_type->is_new) && $op !== FALSE) {
      // Notify the field API that a new bundle has been created.
      field_attach_create_bundle('commerce_product', $product_type->type);

      // Notify other modules that a new product type has been created.
      module_invoke_all('commerce_product_type_insert', $product_type);
    }
    else {
      // Notify other modules that an existing product type has been updated.
      module_invoke_all('commerce_product_type_update', $product_type);
    }

    return $op;
  }

  /**
   * Deletes a product type.
   *
   * @param $type
   *   The machine-readable name of the product type.
   */
  public function delete($type) {
    $product_type = $this->load($type);

    db_delete('commerce_product_type')
      ->condition('type', $type)
      ->execute();

    // Notify the field API that this bundle has been destroyed.
    field_attach_delete_bundle('commerce_product', $type);

    // Notify other modules that this product type has been deleted.
    module_invoke_all('commerce_product_type_delete', $product_type);

    menu_rebuild();
  }

  /**
   * Returns the human readable name of any or all product types.
   *
   * @param $type
   *   Optional parameter specifying the type whose name to return.
   * @return
   *   Either an array of all product type names keyed by the machine name or a
   *     string containing the human readable name for the specified type. If a
   *     type is specified that does not exist, this function returns FALSE.
   */
  public function getName($type = NULL) {
    $product_types = $this->types();

    // Return a type name if specified and it exists.
    if (!empty($type)) {
      if (isset($product_types[$type])) {
        return $product_types[$type]->name;
      }
      else {
        // Return FALSE if it does not exist.
        return FALSE;
      }
    }

    // Otherwise turn the array values into the type name only.
    foreach ($product_types as $key => $value) {
      $product_types[$key] = $value->name;
    }

    return $product_types;
  }

  /**
   * Resets the cached list of product types.
   */
  public function resetCache() {
    $this->types = NULL;
  }
}