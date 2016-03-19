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
      'line_item_id' => NULL,
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
      else {
        // Otherwise if the line item is not new but comes from an entity_create()
        // or similar function call that initializes the created timestamp to an
        // empty string, unset it to prevent destroying existing data in that
        // property on update.
        if ($line_item->created === '') {
          unset($line_item->created);
        }
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
      // First attempt to delete references to each line item, building a list
      // of modified entities to be saved once each after references to all line
      // items have been deleted.
      $reference_fields = commerce_info_fields('commerce_line_item_reference');
      $modified_entities = array();

      foreach ($line_items as $line_item_id => $line_item) {
        // Check the data in every line item reference field.
        foreach ($reference_fields as $field_name => $field) {
          // Query for any entity referencing the deleted line item in this field.
          $query = new EntityFieldQuery();
          $query->fieldCondition($field_name, 'line_item_id', $line_item->line_item_id, '=');
          $result = $query->execute();

          // If no results were returned, continue to next field.
          if (empty($result)) {
            continue;
          }

          // Loop over results for each type of entity returned.
          foreach ($result as $entity_type => $data) {
            // Load the entities of the current type.
            $entities = entity_load($entity_type, array_keys($data));

            // Loop over each entity and remove the reference to the deleted line item.
            foreach ($entities as $entity_id => $entity) {
              // If we have this entity already, get the reference, otherwise
              // store the new entity to save changes later.
              if (!isset($modified_entities[$entity_type . ':' . $entity_id])) {
                $modified_entities[$entity_type . ':' . $entity_id] = array(
                  'entity_type' => $entity_type,
                  'entity' => $entity,
                );
              }

              commerce_entity_reference_delete($entity, $field_name, 'line_item_id', $line_item->line_item_id);
            }
          }
        }
      }

      // Save the entities that were modified through deleted references.
      foreach ($modified_entities as $entity_data) {
        entity_save($entity_data['entity_type'], $entity_data['entity']);
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
