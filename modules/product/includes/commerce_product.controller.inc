<?php

/**
 * @file
 * The controller for the product entity containing the CRUD operations.
 */

/**
 * The controller class for products contains methods for the product CRUD
 * operations. The load method is inherited from the default controller.
 */
class CommerceProductEntityController extends DrupalDefaultEntityController {

  /**
   * Create a default product.
   *
   * @param $type
   *   The machine-readable type of the product.
   *
   * @return
   *   A product object with all default fields initialized.
   */
  public function create($type = '') {
    return (object) array(
      'product_id' => '',
      'is_new' => TRUE,
      'sku' => '',
      'type' => $type,
      'title' => '',
      'uid' => '',
      'status' => 1,
      'created' => '',
      'changed' => '',
    );
  }

  /**
   * Saves a product.
   *
   * @param $product
   *   The full product object to save.
   *
   * @return
   *   The saved product object.
   */
  public function save($product) {
    $transaction = db_transaction();

    try {
      $product->changed = REQUEST_TIME;

      // Give modules the opportunity to prepare field data for saving.
      module_invoke_all('entity_presave', $product, 'commerce_product');
      rules_invoke_all('commerce_product_presave', $product);
      field_attach_presave('commerce_product', $product);

      // If this is a new product...
      if ((isset($product->is_new) && $product->is_new) || empty($product->product_id)) {
        // Set the creation timestamp if not set.
        if (empty($product->created)) {
          $product->created = REQUEST_TIME;
        }

        // Save the new product and fields.
        drupal_write_record('commerce_product', $product);
        field_attach_insert('commerce_product', $product);

        $op = 'insert';
      }
      else {
        // Save the updated product and fields.
        drupal_write_record('commerce_product', $product, 'product_id');
        field_attach_update('commerce_product', $product);

        $op = 'update';
      }

      module_invoke_all('commerce_product_' . $op, $product);
      module_invoke_all('entity_' . $op, $product, 'commerce_product');
      rules_invoke_event('commerce_product_' . $op, $product);

      // Ignore slave server temporarily to give time for the
      // saved product to be propagated to the slave.
      db_ignore_slave();

      return $product;
    }
    catch (Exception $e) {
      $transaction->rollback();
      watchdog_exception('commerce_product', $e);
      throw $e;
    }
  }

  /**
   * Unserializes the data property of loaded products.
   */
  public function attachLoad(&$queried_products, $revision_id = FALSE) {
    foreach ($queried_products as $product_id => &$product) {
      $product->data = unserialize($product->data);
    }

    // Call the default attachLoad() method. This will add fields and call
    // hook_commerce_product_load().
    parent::attachLoad($queried_products, $revision_id);
  }

  /**
   * Deletes multiple products by ID.
   *
   * @param $product_ids
   *   An array of product IDs to delete.
   *
   * @return
   *   TRUE on success, FALSE otherwise.
   */
  public function delete($product_ids) {
    if (!empty($product_ids)) {
      $products = $this->load($product_ids, array());

      // Ensure the products can actually be deleted.
      foreach ((array) $products as $product_id => $product) {
        if (in_array(FALSE, module_invoke_all('commerce_product_can_delete', $product))) {
          unset($products[$product_id]);
        }
      }

      // If none of the specified products can be deleted, return FALSE.
      if (empty($products)) {
        return FALSE;
      }

      $transaction = db_transaction();

      try {
        db_delete('commerce_product')
          ->condition('product_id', $product_ids, 'IN')
          ->execute();

        foreach ($products as $product_id => $product) {
          module_invoke_all('commerce_product_delete', $product);
          field_attach_delete('commerce_product', $product);
          rules_invoke_event('commerce_product_delete', $product);
        }

        // Ignore slave server temporarily to give time for the
        // saved product to be propagated to the slave.
        db_ignore_slave();
      }
      catch (Exception $e) {
        $transaction->rollback();
        watchdog_exception('commerce_product', $e);
        throw $e;
      }

      // Clear the page and block and product_load_multiple caches.
      cache_clear_all();
      $this->resetCache();
    }

    return TRUE;
  }
}
