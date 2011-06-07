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
class CommercePaymentTransactionEntityController extends DrupalCommerceEntityController {

  /**
   * Create a default payment transaction.
   *
   * @param array $values
   *   An array of values to set, keyed by property name.
   *
   * @return
   *   A payment transaction object with all default fields initialized.
   */
  public function create(array $values = array()) {
    global $user;

    return (object) ($values + array(
      'transaction_id' => '',
      'revision_id' => '',
      'uid' => $user->uid,
      'order_id' => 0,
      'payment_method' => '',
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
    ));
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
   * @param $transaction
   *   An optional transaction object.
   *
   * @return
   *   The saved transaction object.
   */
  public function save($transaction, DatabaseTransaction $db_transaction = NULL) {
    if (!isset($db_transaction)) {
      $db_transaction = db_transaction();
      $started_transaction = TRUE;
    }

    try {
      global $user;

      // Determine if we will be inserting a new transaction.
      $transaction->is_new = empty($transaction->transaction_id);

      // Set the timestamp fields.
      if (empty($transaction->created)) {
        $transaction->created = REQUEST_TIME;
      }

      $transaction->changed = REQUEST_TIME;

      $transaction->revision_uid = $user->uid;
      $transaction->revision_timestamp = REQUEST_TIME;

      // Round the amount to ensure it's an integer for storage.
      $transaction->amount = round($transaction->amount);

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

      return parent::save($transaction, $db_transaction);
    }
    catch (Exception $e) {
      if (!empty($started_transaction)) {
        $db_transaction->rollback();
        watchdog_exception($this->entityType, $e);
      }
      throw $e;
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
}
