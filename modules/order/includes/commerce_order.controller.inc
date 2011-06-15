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
    return (object) ($values + array(
      'order_id' => '',
      'order_number' => '',
      'revision_id' => '',
      'uid' => '',
      'mail' => ( !empty($values['uid']) && ($account = user_load($values['uid'])) ) ? $account->mail : '',
      'data' => array(),
      'created' => '',
      'changed' => '',
      'hostname' => '',
    ));
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
   *   The saved order object.
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

      // Update the order total field's value.
      $order_wrapper = entity_metadata_wrapper('commerce_order', $order);

      // Reset the data array of the order total field to only include a
      // base price component, set the currency code from any line item.
      // This is needed in some cases where base price component currency should change.
      $initial_base_currency = commerce_default_currency();
      if (count($order_wrapper->commerce_line_items)) {
        $initial_base_currency = $order_wrapper->commerce_line_items[0]->commerce_total->currency_code->value();
      }
      $base_price = array(
        'amount' => 0,
        'currency_code' => $initial_base_currency,
        'data' => array(),
      );

      $order_wrapper->commerce_order_total->data = commerce_price_component_add($base_price, 'base_price', $base_price, TRUE);

      // Then loop over each line item and add its total to the order total.
      $total = 0;
      $order_total_currency_code = '';
      $same_currency = TRUE;

      foreach ($order_wrapper->commerce_line_items as $delta => $line_item_wrapper) {
        // Convert the line item's total to the order's currency for totalling.
        $component_total = commerce_price_component_total($line_item_wrapper->commerce_total->value());

        // Set the order total currency code to be the currency if the first line item.
        if ($order_total_currency_code === '') {
          $order_total_currency_code = $component_total['currency_code'];
        }

        // If we get a mix of currencies, convert to default currency code.
        // This only need to happen once, if currencies are mixed we always
        // use the defalt currency code.
        if ($order_total_currency_code !== $component_total['currency_code'] && $same_currency) {
          $same_currency = FALSE;
          // Convert the total to default currency code.
          $total = commerce_currency_convert(
            $total,
            $order_total_currency_code,
            commerce_default_currency()
          );
          $order_total_currency_code = commerce_default_currency();
        }

        // Add the totals.
        $total += commerce_currency_convert(
          $component_total['amount'],
          $component_total['currency_code'],
          $order_total_currency_code
        );

        // Combine the line item total's component prices into the order total.
        $order_wrapper->commerce_order_total->data = commerce_price_components_combine(
          $order_wrapper->commerce_order_total->value(),
          $line_item_wrapper->commerce_total->value()
        );
      }

      // We need to recreate the price component to make it match the order total
      if ($same_currency === FALSE) {
        $base_price = array(
          'amount' => 0,
          'currency_code' => $order_total_currency_code,
          'data' => array(),
        );

        $order_wrapper->commerce_order_total->data = commerce_price_component_add($base_price, 'base_price', $base_price, TRUE);

        foreach ($order_wrapper->commerce_line_items as $line_item_wrapper) {
          // Combine the line item total's component prices into the order total.
          $order_wrapper->commerce_order_total->data = commerce_price_components_combine(
            $order_wrapper->commerce_order_total->value(),
            $line_item_wrapper->commerce_total->value()
          );
        }
      }

      // Update the commerce_order_total with the result from the loop above.
      $order_wrapper->commerce_order_total->amount = $total;
      $order_wrapper->commerce_order_total->currency_code = $order_total_currency_code;

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
