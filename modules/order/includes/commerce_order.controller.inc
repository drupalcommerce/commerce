<?php

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
   * @param $uid
   *   Optionally the uid of the customer for the order.
   * @param $status
   *   Optionally the order status of the new order.
   * @param $type
   *   The type of the order; defaults to the standard 'order' type.
   *
   * @return
   *   An order object with all default fields initialized.
   */
  public function create($uid = 0, $status = '', $type = 'commerce_order') {
    return (object) array(
      'order_id' => '',
      'order_number' => '',
      'revision_id' => '',
      'type' => $type,
      'uid' => $uid,
      'mail' => ( $uid > 0 && ($account = user_load($uid)) ) ? $account->mail : '',
      'status' => $status,
      'data' => array(),
      'created' => '',
      'changed' => '',
    );
  }

  /**
   * Saves an order.
   *
   * When saving an order without an order ID, this function will create a new
   * order at that time. For new orders, it will also determine and save the
   * order number and then save the initial revision of the order. Subsequent
   * orders that should be saved as new revisions should set $order->revision to
   * TRUE and include a log string in $order->log.
   *
   * @param $order
   *   The full order object to save.
   *
   * @return
   *   The saved order object.
   */
  public function save($order) {
    $transaction = db_transaction();

    try {
      global $user;

      // Determine if we will be inserting a new order.
      $order->is_new = empty($order->order_id);

      // Set the timestamp fields.
      if (empty($order->created)) {
        $order->created = REQUEST_TIME;
        $order->hostname = ip_address();
      }

      $order->changed = REQUEST_TIME;

      $order->revision_timestamp = REQUEST_TIME;
      $order->revision_hostname = ip_address();
      $update_order = TRUE;

      // Update the order total field's value.
      $order_wrapper = entity_metadata_wrapper('commerce_order', $order);

      // First set the currency to the site default if this is a new order.
      if ($order->is_new || $order_wrapper->commerce_order_total->currency_code->value() == NULL) {
        $order_wrapper->commerce_order_total->currency_code = commerce_default_currency();
      }

      // Next reset the data array of the order total field to only include a
      // base price component.
      $base_price = array(
        'amount' => 0,
        'currency_code' => $order_wrapper->commerce_order_total->currency_code->value(),
        'data' => array(),
      );

      $order_wrapper->commerce_order_total->data = commerce_price_component_add($base_price, 'base_price', $base_price, TRUE);

      // Then loop over each line item and add its total to the order total.
      $total = 0;

      foreach ($order_wrapper->commerce_line_items as $delta => $line_item_wrapper) {
        // Convert the line item's total to the order's currency for totalling.
        $component_total = commerce_price_component_total($line_item_wrapper->commerce_total->value());

        $total += commerce_currency_convert(
          $component_total['amount'],
          $component_total['currency_code'],
          $order_wrapper->commerce_order_total->currency_code->value()
        );

        // Combine the line item total's component prices into the order total.
        $order_wrapper->commerce_order_total->data = commerce_price_components_combine(
          $order_wrapper->commerce_order_total->value(),
          $line_item_wrapper->commerce_total->value()
        );
      }

      $order_wrapper->commerce_order_total->amount = $total;

      // Give modules the opportunity to prepare field data for saving.
      module_invoke_all('entity_presave', $order, 'commerce_order');
      rules_invoke_all('commerce_order_presave', $order);
      field_attach_presave('commerce_order', $order);

      if ($order->is_new || !empty($order->revision)) {
        // When inserting either a new order or revision, $order->log must be set
        // because {commerce_order_revision}.log is a text column and therefore
        // cannot have a default value. However, it might not be set at this
        // point, so we ensure that it is at least an empty string in that case.
        if (!isset($order->log)) {
          $order->log = '';
        }
      }
      elseif (empty($order->log)) {
        // If we are updating an existing order without adding a new revision,
        // we need to make sure $order->log is unset whenever it is empty. As
        // long as $order->log is unset, drupal_write_record() will not attempt
        // to update the existing database column when re-saving the revision.
        unset($order->log);
      }

      // When saving a new order revision, unset any existing $order->revision_id
      // to ensure a new revision will actually be created and store the old
      // revision ID in a separate property for order hook implementations.
      if (!$order->is_new && !empty($order->revision) && $order->revision_id) {
        $order->old_revision_id = $order->revision_id;
        unset($order->revision_id);
      }

      // If this is a new order...
      if ($order->is_new) {
        // Save the new order.
        drupal_write_record('commerce_order', $order);

        // Save the order number.
        // TODO: Provide token support for order number patterns.
        if (empty($order->order_number)) {
          $order->order_number = $order->order_id;
        }

        db_update('commerce_order')
          ->fields(array('order_number' => $order->order_number))
          ->condition('order_id', $order->order_id)
          ->execute();

        // Save the initial revision.
        $this->saveRevision($order, $user->uid);

        $op = 'insert';
      }
      else {
        // Save the updated order.
        drupal_write_record('commerce_order', $order, 'order_id');

        // If a new order revision was requested, save a new record for that;
        // otherwise, update the order revision record that matches the value
        // of $order->revision_id.
        if (!empty($order->revision)) {
          $this->saveRevision($order, $user->uid);
        }
        else {
          $this->saveRevision($order, $user->uid, TRUE);
          $update_order = FALSE;
        }

        $op = 'update';
      }

      // If the revision ID is new or updated, save it to the order.
      if ($update_order) {
        db_update('commerce_order')
          ->fields(array('revision_id' => $order->revision_id))
          ->condition('order_id', $order->order_id)
          ->execute();
      }

      // Save fields.
      $function = 'field_attach_' . $op;
      $function('commerce_order', $order);

      module_invoke_all('commerce_order_' . $op, $order);
      module_invoke_all('entity_' . $op, $order, 'commerce_order');
      rules_invoke_event('commerce_order_' . $op, $order);

      // Clear internal properties.
      unset($order->is_new);
      $this->resetCache();

      // Ignore slave server temporarily to give time for the saved order to be
      // propagated to the slave.
      db_ignore_slave();

      return $order;
    }
    catch (Exception $e) {
      $transaction->rollback();
      watchdog_exception('commerce_order', $e);
      throw $e;
    }
  }

  /**
   * Saves an order revision with the uid of the current user.
   *
   * @param $order
   *   The fully loaded order object.
   * @param $uid
   *   The user's uid for the current revision.
   * @param $update
   *   TRUE or FALSE indicating whether or not the existing revision should be
   *     updated instead of a new one created.
   */
  function saveRevision($order, $uid, $update = FALSE) {
    $order->revision_uid = $uid;

    // Update the existing revision if specified.
    if ($update) {
      drupal_write_record('commerce_order_revision', $order, 'revision_id');
    }
    else {
      // Otherwise insert a new revision. This will automatically update $order
      // to include the revision_id.
      drupal_write_record('commerce_order_revision', $order);
    }
  }

  /**
   * Unserializes the data property of loaded orders.
   */
  public function attachLoad(&$queried_orders, $revision_id = FALSE) {
    foreach ($queried_orders as $order_id => &$order) {
      $order->data = unserialize($order->data);
    }

    // Call the default attachLoad() method. This will add fields and call
    // hook_commerce_order_load().
    parent::attachLoad($queried_orders, $revision_id);

    // Clone the order within the order object for use in Rules.
    $order->original = clone($order);
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
        // Loop through the orders to delete.
        foreach ($orders as $order_id => $order) {
          // Alert other modules and systems that the order is being deleted.
          module_invoke_all('commerce_order_delete', $order);
          field_attach_delete('commerce_order', $order);
          rules_invoke_event('commerce_order_delete', $order);
        }

        db_delete('commerce_order')
          ->condition('order_id', $order_ids, 'IN')
          ->execute();
        db_delete('commerce_order_revision')
          ->condition('order_id', $order_ids, 'IN')
          ->execute();

        // Ignore slave server temporarily to give time for the
        // saved order to be propagated to the slave.
        db_ignore_slave();
      }
      catch (Exception $e) {
        $transaction->rollback();
        watchdog_exception('commerce_order', $e);
        throw $e;
      }

      // Clear the page and block and order_load_multiple caches.
      cache_clear_all();
      $this->resetCache();
    }

    return TRUE;
  }
}
