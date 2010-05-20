<?php
// $Id$

/**
 * @file
 * The controller for the order entity containing the CRUD operations.
 */

/**
 * The controller class for orders contains methods for the order CRUD
 * operations. The load method is inherited from the default controller.
 */
class CommerceOrderEntityController extends DrupalDefaultEntityController {

  /**
   * Create a default order.
   *
   * @param $customer_uid
   *   Optionally the uid of the customer for the order.
   *
   * @return
   *   An order object with all default fields initialized.
   */
  public function create($customer_uid = 0, $status = '') {
    return (object) array(
      'order_id' => '',
      'order_number' => '',
      'revision_id' => '',
      'creator_uid' => '',
      'customer_uid' => $customer_uid,
      'mail' => '',
      'status' => $status,
      'created' => '',
      'changed' => '',
    );
  }

  /**
   * Saves an order.
   *
   * TODO: Implement order versioning, see http://drupal.org/node/736486.
   *
   * @param $order
   *   The full order object to save.
   *
   * @return
   *   The saved order object on success, FALSE on failure.
   */
  public function save($order) {
    $transaction = db_transaction();

    try {
      $order->changed = time();

      // Save a copy of the unchanged order for rules in case of updates.
      // see commerce_order_rules_var_unchanged().
      $unchanged = &drupal_static('commerce_order_unchanged', array());
      if (!empty($order->order_id)) {
        $unchanged[$order->order_id] = clone commerce_order_load($order->order_id);
      }

      // Give modules the opportunity to prepare field data for saving.
      field_attach_presave('commerce_order', $order);
      module_invoke_all('commerce_order_presave', $order);
      rules_invoke_event('commerce_order_presave', $order);

      // If this is a new order...
      if (empty($order->order_id)) {
        // Set the creation timestamp.
        $order->created = time();

        // Save the new order and fields.
        drupal_write_record('commerce_order', $order);
        field_attach_insert('commerce_order', $order);

        // Save the order number.
        // TODO: Provide token support for order number patterns.
        $order->order_number = $order->order_id;

        db_update('commerce_order')
          ->fields(array('order_number' => $order->order_number))
          ->condition('order_id', $order->order_id)
          ->execute();

        $op = 'insert';
      }
      else {
        // Save the updated order and fields.
        drupal_write_record('commerce_order', $order, 'order_id');
        field_attach_update('commerce_order', $order);

        $op = 'update';
      }

      module_invoke_all('commerce_order_' . $op, $order);
      entity_invoke($op, 'commerce_order', $order);
      rules_invoke_event('commerce_order_' . $op, $order);

      // Ignore slave server temporarily to give time for the
      // saved order to be propagated to the slave.
      db_ignore_slave();

      return $order;
    }
    catch (Exception $e) {
      $transaction->rollback('commerce order', $e->getMessage(), array(), WATCHDOG_ERROR);
      return FALSE;
    }
  }

  /**
   * Deletes multiple orders by ID.
   *
   * @param $order_ids
   *   An array of order IDs to delete.
   *
   * @return
   *   TRUE on success, FALSE otherwise.
   */
  public function delete($order_ids) {
    if (!empty($order_ids)) {
      $orders = $this->load($order_ids, array());

      $transaction = db_transaction();

      try {
        db_delete('commerce_order')
          ->condition('order_id', $order_ids, 'IN')
          ->execute();

        // TODO: Delete any line items associated with this order.

        foreach ($orders as $order_id => $order) {
          module_invoke_all('commerce_order_delete', $order);
          field_attach_delete('commerce_order', $order);
          rules_invoke_event('commerce_order_delete', $order);
        }

        // Ignore slave server temporarily to give time for the
        // saved order to be propagated to the slave.
        db_ignore_slave();
      }
      catch (Exception $e) {
        $transaction->rollback('commerce order', $e->getMessage(), array(), WATCHDOG_ERROR);
        return FALSE;
      }

      // Clear the page and block and order_load_multiple caches.
      cache_clear_all();
      $this->resetCache();
    }

    return TRUE;
  }
}
