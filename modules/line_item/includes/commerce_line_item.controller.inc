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
    return (object) ($values + array(
      'line_item_id' => '',
      'order_id' => 0,
      'type' => '',
      'line_item_label' => '',
      'quantity' => 1,
      'created' => '',
      'changed' => '',
      'data' => array(),
    ));
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
   *   The saved line item object.
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
      $wrapper->commerce_total->amount = $line_item->quantity * $wrapper->commerce_unit_price->amount->value();
      $wrapper->commerce_total->currency_code = $wrapper->commerce_unit_price->currency_code->value();

      // Add the components multiplied by the quantity to the data array.
      $data = $wrapper->commerce_unit_price->data->value();

      if (empty($data['components'])) {
        $data['components'] = array();
      }
      else {
        foreach ($data['components'] as $key => $component) {
          $data['components'][$key]['price']['amount'] *= $line_item->quantity;
        }
      }

      // Set the updated data array to the total price.
      $wrapper->commerce_total->data = $data;

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
}
