<?php
// $Id$

/**
 * @file
 * The controller for the product entity containing the CRUD operations.
 */

/**
 * The controller class for products contains all the CRUD operations for
 * commerce products. The default loader, provided by core, is used for the
 * loading operation.
 */
class CommerceProductEntityController extends DrupalDefaultEntityController {

  /**
   * Create a default product.
   *
   * @param $type
   *   The machine-readable type of the product.
   * @return
   *   A product object with all default fields initialized.
   */
  public function create($type = '') {
    return (object) array(
      'product_id' => '',
      'sku' => '',
      'type' => $type,
      'title' => '',
      'uid' => '',
      'created' => '',
      'changed' => '',
    );
  }

  /**
   * Saves a product.
   *
   * @param $product
   *   The full product object to save.
   * @return
   *   The saved product object on success, FALSE on failure.
   */
  public function save($product) {
    $transaction = db_transaction();

    try {
      $product->changed = time();

      // Save a copy of the unchanged product for rules in case of updates.
      // see commerce_product_rules_var_unchanged().
      $unchanged = &drupal_static('commerce_product_unchanged', array());
      if (!empty($product->product_id)) {
        $unchanged[$product->product_id] = clone commerce_product_load($product->product_id);
      }

      // Give modules the opportunity to prepare field data for saving.
      field_attach_presave('commerce_product', $product);
      module_invoke_all('commerce_product_presave', $product);
      rules_invoke_event('commerce_product_presave', $product);

      // If this is a new product...
      if (empty($product->product_id)) {
        // Set the creation timestamp.
        $product->created = time();

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
      entity_invoke($op, 'commerce_product', $product);
      rules_invoke_event('commerce_product_' . $op, $product);

      // Ignore slave server temporarily to give time for the
      // saved product to be propagated to the slave.
      db_ignore_slave();

      return $product;
    }
    catch (Exception $e) {
      $transaction->rollback('commerce product', $e->getMessage(), array(), WATCHDOG_ERROR);
      return FALSE;
    }
  }

  /**
   * Deletes multiple products by ID.
   *
   * @param $product_ids
   *   An array of product IDs to delete.
   * @return
   *   TRUE on success, FALSE otherwise.
   */
  public function delete($product_ids) {
    if (!empty($product_ids)) {
      $products = $this->load($product_ids, array());

      // TODO: We'll need to ensure products on orders cannot be deleted when we
      // have orders implemented.

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
        $transaction->rollback('commerce product', $e->getMessage(), array(), WATCHDOG_ERROR);
        return FALSE;
      }

      // Clear the page and block and product_load_multiple caches.
      cache_clear_all();
      $this->resetCache();
    }

    return TRUE;
  }
}
