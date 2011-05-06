<?php

/**
 * @file
 * The controller for the line item entity containing the CRUD operations.
 */

/**
 * The controller class for line items contains methods for the line item CRUD
 * operations. The load method is inherited from the default controller.
 */
class CommerceLineItemEntityController extends DrupalDefaultEntityController {

  /**
   * Create a default line item.
   *
   * @param $type
   *   The machine-readable type of the line item.
   * @param $order_id
   *   The ID of the order the line item belongs to (if available).
   *
   * @return
   *   A line item object with all default fields initialized.
   */
  public function create($type = '', $order_id = 0) {
    return (object) array(
      'line_item_id' => '',
      'order_id' => $order_id,
      'type' => $type,
      'line_item_label' => '',
      'quantity' => 1,
      'created' => '',
      'changed' => '',
    );
  }

  /**
   * Saves a line item.
   *
   * @param $line_item
   *   The full line item object to save.
   *
   * @return
   *   The saved line item object.
   */
  public function save($line_item) {
    $wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);
    $transaction = db_transaction();

    try {
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

      // Give modules the opportunity to prepare field data for saving.
      module_invoke_all('entity_presave', $line_item, 'commerce_line_item');
      rules_invoke_all('commerce_line_item_presave', $line_item);
      field_attach_presave('commerce_line_item', $line_item);

      // If this is a new line item...
      if (empty($line_item->line_item_id)) {
        // Set the creation timestamp.
        $line_item->created = REQUEST_TIME;

        // Save the new line item and fields.
        drupal_write_record('commerce_line_item', $line_item);

        $op = 'insert';
      }
      else {
        // Save the updated line item and fields.
        drupal_write_record('commerce_line_item', $line_item, 'line_item_id');

        $op = 'update';
      }

      // Save fields.
      $function = 'field_attach_' . $op;
      $function('commerce_line_item', $line_item);

      module_invoke_all('commerce_line_item_' . $op, $line_item);
      module_invoke_all('entity_' . $op, $line_item, 'commerce_line_item');
      rules_invoke_event('commerce_line_item_' . $op, $line_item);

      // Ignore slave server temporarily to give time for the saved line item to
      // be propagated to the slave.
      db_ignore_slave();

      return $line_item;
    }
    catch (Exception $e) {
      $transaction->rollback();
      watchdog_exception('commerce_line_item', $e);
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
   * Deletes multiple line items by ID.
   *
   * @param $line_item_ids
   *   An array of line item IDs to delete.
   *
   * @return
   *   TRUE on success, FALSE otherwise.
   */
  public function delete($line_item_ids) {
    if (!empty($line_item_ids)) {
      $line_items = $this->load($line_item_ids, array());

      $transaction = db_transaction();

      try {
        db_delete('commerce_line_item')
          ->condition('line_item_id', $line_item_ids, 'IN')
          ->execute();

        foreach ($line_items as $line_item_id => $line_item) {
          module_invoke_all('commerce_line_item_delete', $line_item);
          field_attach_delete('commerce_line_item', $line_item);
          rules_invoke_event('commerce_line_item_delete', $line_item);
        }

        // Ignore slave server temporarily to give time for the
        // saved line item to be propagated to the slave.
        db_ignore_slave();
      }
      catch (Exception $e) {
        $transaction->rollback();
        watchdog_exception('commerce_line_item', $e);
        throw $e;
      }

      // Clear the page and block and line_item_load_multiple caches.
      cache_clear_all();
      $this->resetCache();
    }

    return TRUE;
  }
}
