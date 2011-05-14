<?php

/**
 * @file
 * The controller for the payment transaction entity containing the CRUD operations.
 */

/**
 * The controller class for payment transactions contains methods for the
 * transaction CRUD operations. The load method is inherited from the default
 * controller.
 */
class CommercePaymentTransactionEntityController extends DrupalDefaultEntityController {

  /**
   * Create a default payment transaction.
   *
   * @param $method_id
   *   The ID of the payment method for the transaction.
   * @param $order_id
   *   The ID of the order the transaction is for.
   *
   * @return
   *   A payment transaction object with all default fields initialized.
   */
  public function create($method_id = '', $order_id = 0) {
    global $user;

    return (object) array(
      'transaction_id' => '',
      'revision_id' => '',
      'uid' => $user->uid,
      'order_id' => $order_id,
      'payment_method' => $method_id,
      'instance_id' => '',
      'remote_id' => '',
      'message' => '',
      'message_variables' => array(),
      'amount' => 0,
      'currency_code' => '',
      'status' => '',
      'remote_status' => '',
      'payload' => array(),
      'created' => '',
      'changed' => '',
    );
  }

  /**
   * Saves a payment transaction.
   *
   * When saving a transaction without an ID, this function will create a new
   * transaction at that time. Subsequent transactions that should be saved as
   * new revisions should set $transaction->revision to TRUE and include a log
   * string in $transaction->log.
   *
   * @param $transaction
   *   The full transaction object to save.
   *
   * @return
   *   The saved transaction object.
   */
  public function save($transaction) {
    $db_transaction = db_transaction();

    try {
      // Determine if we will be inserting a new transaction.
      $transaction->is_new = empty($transaction->transaction_id);

      // Set the timestamp fields.
      if (empty($transaction->created)) {
        $transaction->created = REQUEST_TIME;
      }

      $transaction->changed = REQUEST_TIME;

      $transaction->revision_timestamp = REQUEST_TIME;
      $update_transaction = TRUE;

      // Round the amount to ensure it's an integer for storage.
      $transaction->amount = round($transaction->amount);

      // Give modules the opportunity to prepare field data for saving.
      module_invoke_all('entity_presave', $transaction, 'commerce_payment_transaction');
      rules_invoke_all('commerce_payment_transaction_presave', $transaction);
      field_attach_presave('commerce_payment_transaction', $transaction);

      if ($transaction->is_new || !empty($transaction->revision)) {
        // When inserting either a new transaction or revision, $transaction->log
        // must be set because {commerce_payment_transaction_revision}.log is a
        // text column and therefore cannot have a default value. However, it
        // might not be set at this point, so we ensure that it is at least an
        // empty string in that case.
        if (!isset($transaction->log)) {
          $transaction->log = '';
        }
      }
      elseif (empty($transaction->log)) {
        // If we are updating an existing transaction without adding a new
        // revision, we need to make sure $transaction->log is unset whenever it
        // is empty.  As long as $transaction->log is unset, drupal_write_record()
        // will not attempt to update the existing database column when re-saving
        // the revision.
        unset($transaction->log);
      }

      // When saving a new transaction revision, unset any existing
      // $transaction->revision_id to ensure a new revision will actually be
      // created and store the old revision ID in a separate property for
      // transaction hook implementations.
      if (!$transaction->is_new && !empty($transaction->revision) && $transaction->revision_id) {
        $transaction->old_revision_id = $transaction->revision_id;
        unset($transaction->revision_id);
      }

      // If this is a new transaction...
      if ($transaction->is_new) {
        // Save the new transaction and fields.
        drupal_write_record('commerce_payment_transaction', $transaction);

        // Save the initial revision.
        $this->saveRevision($transaction);

        $op = 'insert';
      }
      else {
        // Save the updated transaction and fields.
        drupal_write_record('commerce_payment_transaction', $transaction, 'transaction_id');

        // If a new transaction revision was requested, save a new record for
        // that; otherwise, update the transaction revision record that matches
        // the value of $transaction->revision_id.
        if (!empty($transaction->revision)) {
          $this->saveRevision($transaction);
        }
        else {
          $this->saveRevision($transaction, TRUE);
          $update_transaction = FALSE;
        }

        $op = 'update';
      }

      // If the revision ID is new or updated, save it to the transaction.
      if ($update_transaction) {
        db_update('commerce_payment_transaction')
          ->fields(array('revision_id' => $transaction->revision_id))
          ->condition('transaction_id', $transaction->transaction_id)
          ->execute();
      }

      // Save fields.
      $function = 'field_attach_' . $op;
      $function('commerce_payment_transaction', $transaction);

      module_invoke_all('commerce_payment_transaction_' . $op, $transaction);
      module_invoke_all('entity_' . $op, $transaction, 'commerce_payment_transaction');
      rules_invoke_event('commerce_payment_transaction_' . $op, $transaction);

      // Clear internal properties.
      unset($transaction->is_new);
      $this->resetCache();

      // Ignore slave server temporarily to give time for the
      // saved transaction to be propagated to the slave.
      db_ignore_slave();

      return $transaction;
    }
    catch (Exception $e) {
      $db_transaction->rollback();
      watchdog_exception('commerce_payment', $e);
      throw $e;
    }
  }

  /**
   * Saves a payment transaction revision.
   *
   * @param $transaction
   *   The fully loaded transaction object.
   * @param $update
   *   TRUE or FALSE indicating whether or not the existing revision should be
   *     updated instead of a new one created.
   */
  function saveRevision($transaction, $update = FALSE) {
    global $user;

    $transaction->revision_uid = $user->uid;

    // Update the existing revision if specified.
    if ($update) {
      drupal_write_record('commerce_payment_transaction_revision', $transaction, 'revision_id');
    }
    else {
      // Otherwise insert a new revision. This will automatically update $transaction
      // to include the revision_id.
      drupal_write_record('commerce_payment_transaction_revision', $transaction);
    }
  }

  /**
   * Unserializes the message_variables and payload properties of loaded payment
   *   transactions.
   */
  public function attachLoad(&$queried_transactions, $revision_id = FALSE) {
    foreach ($queried_transactions as $transaction_id => &$transaction) {
      $transaction->message_variables = unserialize($transaction->message_variables);
      $transaction->payload = unserialize($transaction->payload);
      $transaction->data = unserialize($transaction->data);
    }

    // Call the default attachLoad() method. This will add fields and call
    // hook_user_load().
    parent::attachLoad($queried_transactions, $revision_id);

  }

  /**
   * Deletes multiple payment transactions by ID.
   *
   * @param $transaction_ids
   *   An array of transaction IDs to delete.
   *
   * @return
   *   TRUE on success, FALSE otherwise.
   */
  public function delete($transaction_ids) {
    if (!empty($transaction_ids)) {
      $transactions = $this->load($transaction_ids, array());

      $db_transaction = db_transaction();

      try {
        db_delete('commerce_payment_transaction')
          ->condition('transaction_id', $transaction_ids, 'IN')
          ->execute();

        foreach ($transactions as $transaction_id => $transaction) {
          module_invoke_all('commerce_payment_transaction_delete', $transaction);
          field_attach_delete('commerce_payment_transaction', $transaction);
          rules_invoke_event('commerce_payment_transaction_delete', $transaction);
        }

        // Ignore slave server temporarily to give time for the
        // saved transaction to be propagated to the slave.
        db_ignore_slave();
      }
      catch (Exception $e) {
        $db_transaction->rollback();
        watchdog_exception('commerce_payment', $e);
        throw $e;
      }

      // Clear the page and block and commerce_payment_transaction_load_multiple caches.
      cache_clear_all();
      $this->resetCache();
    }

    return TRUE;
  }
}
