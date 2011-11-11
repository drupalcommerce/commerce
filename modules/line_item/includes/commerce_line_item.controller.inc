<?php

/**
 * @file
 * The controller for the line item entity containing the CRUD operations.
 */

/**
 * The controller class for line items contains methods for the line item CRUD
 * operations. The load method is inherited from the default controller.
 */
class CommerceLineItemEntityController extends DrupalCommerceEntityController {

  /**
   * Create a default line item.
   *
   * @param array $values
   *   An array of values to set, keyed by property name.
   *
   * @return
   *   A line item object with all default fields initialized.
   */
  public function create(array $values = array()) {
    $values += array(
      'line_item_id' => '',
      'order_id' => 0,
      'type' => '',
      'line_item_label' => '',
      'quantity' => 1,
      'created' => '',
      'changed' => '',
      'data' => array(),
    );

    return parent::create($values);
  }

  /**
   * Saves a line item.
   *
   * @param $line_item
   *   The full line item object to save.
   * @param $transaction
   *   An optional transaction object.
   *
   * @return
   *   SAVED_NEW or SAVED_UPDATED depending on the operation performed.
   */
  public function save($line_item, DatabaseTransaction $transaction = NULL) {
    if (!isset($transaction)) {
      $transaction = db_transaction();
      $started_transaction = TRUE;
    }

    $wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);

    try {
      // Set the timestamp fields.
      if (empty($line_item->line_item_id) && empty($line_item->created)) {
        $line_item->created = REQUEST_TIME;
      }

      $line_item->changed = REQUEST_TIME;

      // Update the total of the line item based on the quantity and unit price.
      $unit_price = commerce_price_wrapper_value($wrapper, 'commerce_unit_price', TRUE);

      $wrapper->commerce_total->amount = $line_item->quantity * $unit_price['amount'];
      $wrapper->commerce_total->currency_code = $unit_price['currency_code'];

      // Add the components multiplied by the quantity to the data array.
      if (empty($unit_price['data']['components'])) {
        $unit_price['data']['components'] = array();
      }
      else {
        foreach ($unit_price['data']['components'] as $key => &$component) {
          $component['price']['amount'] *= $line_item->quantity;
        }
      }

      // Set the updated data array to the total price.
      $wrapper->commerce_total->data = $unit_price['data'];

      return parent::save($line_item, $transaction);
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
   * Unserializes the data property of loaded line items.
   */
  public function attachLoad(&$queried_line_items, $revision_id = FALSE) {
    foreach ($queried_line_items as $line_item_id => &$line_item) {
      $line_item->data = unserialize($line_item->data);
    }

    // Call the default attachLoad() method. This will add fields and call
    // hook_commerce_line_item_load().
    parent::attachLoad($queried_line_items, $revision_id);
  }

  /**
   * Delete permanently saved line items.
   *
   * In case of failures, an exception is thrown.
   *
   * @param $line_item_ids
   *   An array of line item IDs to delete.
   * @param $transaction
   *   An optional transaction object to pass thru. If passed the caller is
   *   responsible for rolling back the transaction if something goes wrong.
   */
  public function delete($line_item_ids, DatabaseTransaction $transaction = NULL) {
    $line_items = $line_item_ids ? $this->load($line_item_ids) : FALSE;

    if (!$line_items) {
      // Do nothing, in case invalid or no ids have been passed.
      return;
    }

    if (!isset($transaction)) {
      $transaction = db_transaction();
      $started_transaction = TRUE;
    }

    try {
      // First attempt to delete references for the given line items.
      foreach ($line_items as $line_item_id => $line_item) {
        commerce_line_item_delete_references($line_item);
      }

      return parent::delete($line_item_ids, $transaction);
    }
    catch (Exception $e) {
      if (!empty($started_transaction)) {
        $transaction->rollback();
        watchdog_exception($this->entityType, $e);
      }
      throw $e;
    }
  }
}
