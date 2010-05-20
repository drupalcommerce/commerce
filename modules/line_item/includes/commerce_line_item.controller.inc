<?php
// $Id$

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
   * @return
   *   A line item object with all default fields initialized.
   */
  public function create($type = '') {
    return (object) array(
      'line_item_id' => '',
      'type' => $type,
      'line_item_label' => '',
      'quantity' => 1,
    );
  }

  /**
   * Saves a line item.
   *
   * @param $line_item
   *   The full line item object to save.
   * @return
   *   The saved line item object on success, FALSE on failure.
   */
  public function save($line_item) {
    $transaction = db_transaction();

    try {
      // Save a copy of the unchanged line item for rules in case of updates.
      // see commerce_line_item_rules_var_unchanged().
      $unchanged = &drupal_static('commerce_line_item_unchanged', array());
      if (!empty($line_item->line_item_id)) {
        $unchanged[$line_item->line_item_id] = clone commerce_line_item_load($line_item->line_item_id);
      }

      // Give modules the opportunity to prepare field data for saving.
      field_attach_presave('commerce_line_item', $line_item);
      module_invoke_all('commerce_line_item_presave', $line_item);
      rules_invoke_event('commerce_line_item_presave', $line_item);

      // Update the total of the line item based on the quantity and unit price.
      $line_item->total[LANGUAGE_NONE][0]['price'] = $line_item->quantity * $line_item->unit_price[LANGUAGE_NONE][0]['price'];

      // If this is a new line item...
      if (empty($line_item->line_item_id)) {
        // Save the new line item and fields.
        drupal_write_record('commerce_line_item', $line_item);
        field_attach_insert('commerce_line_item', $line_item);

        $op = 'insert';
      }
      else {
        // Save the updated line item and fields.
        drupal_write_record('commerce_line_item', $line_item, 'line_item_id');
        field_attach_update('commerce_line_item', $line_item);

        $op = 'update';
      }

      module_invoke_all('commerce_line_item_' . $op, $line_item);
      entity_invoke($op, 'commerce_line_item', $line_item);
      rules_invoke_event('commerce_line_item_' . $op, $line_item);

      // Ignore slave server temporarily to give time for the
      // saved line item to be propagated to the slave.
      db_ignore_slave();

      return $line_item;
    }
    catch (Exception $e) {
      $transaction->rollback('commerce line item', $e->getMessage(), array(), WATCHDOG_ERROR);
      return FALSE;
    }
  }

  /**
   * Deletes multiple line items by ID.
   *
   * @param $line_item_ids
   *   An array of line item IDs to delete.
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

        // TODO: Delete any references to this line item.

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
        $transaction->rollback('commerce line item', $e->getMessage(), array(), WATCHDOG_ERROR);
        return FALSE;
      }

      // Clear the page and block and line_item_load_multiple caches.
      cache_clear_all();
      $this->resetCache();
    }

    return TRUE;
  }
}
