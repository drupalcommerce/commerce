<?php

/**
 * @file
 * The controller for the order entity containing the CRUD operations.
 */

/**
 * The controller class for orders contains methods for the order CRUD
 * operations. The load method is inherited from the default controller.
 */
class CommerceOrderEntityController extends DrupalCommerceEntityController {

  /**
   * Create a default order.
   *
   * @param array $values
   *   An array of values to set, keyed by property name.
   *
   * @return
   *   An order object with all default fields initialized.
   */
  public function create(array $values = array()) {
    $values += array(
      'order_id' => NULL,
      'order_number' => NULL,
      'revision_id' => NULL,
      'uid' => '',
      'mail' => ( !empty($values['uid']) && ($account = user_load($values['uid'])) ) ? $account->mail : '',
      'data' => array(),
      'created' => '',
      'changed' => '',
      'hostname' => '',
    );

    return parent::create($values);
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
   * @param $transaction
   *   An optional transaction object.
   *
   * @return
   *   SAVED_NEW or SAVED_UPDATED depending on the operation performed.
   */
  public function save($order, DatabaseTransaction $transaction = NULL) {
    if (!isset($transaction)) {
      $transaction = db_transaction();
      $started_transaction = TRUE;
    }

    try {
      global $user;

      // Determine if we will be inserting a new order.
      $order->is_new = empty($order->order_id);

      // Set the timestamp fields.
      if ($order->is_new && empty($order->created)) {
        $order->created = REQUEST_TIME;
        $order->hostname = ip_address();
      }

      $order->changed = REQUEST_TIME;

      $order->revision_timestamp = REQUEST_TIME;
      $order->revision_hostname = ip_address();
      $order->revision_uid = $user->uid;

      // Recalculate the order total using the current line item data.
      commerce_order_calculate_total($order);

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

      return parent::save($order, $transaction);
    }
    catch (Exception $e) {
      if (!empty($started_transaction)) {
        $transaction->rollback();
        watchdog_exception($this->entityType, $e);
      }
      throw $e;
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
  }

}
