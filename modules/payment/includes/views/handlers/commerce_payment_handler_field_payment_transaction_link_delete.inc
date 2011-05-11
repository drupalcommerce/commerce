<?php

/**
 * Field handler to present a link to delete a payment transaction.
 */
class commerce_payment_handler_field_payment_transaction_link_delete extends commerce_payment_handler_field_payment_transaction_link {

  function render($values) {
    // Ensure the user has access to delete this payment transaction.
    $transaction_id = $this->get_value($values, 'transaction_id');
    $order_id = $this->get_value($values, 'order_id');
    $order = commerce_order_load($order_id);
    $transaction = commerce_payment_transaction_load($transaction_id);

    if (commerce_payment_transaction_access('delete', $order, $transaction)) {
      $text = !empty($this->options['text']) ? $this->options['text'] : t('delete');
      return l($text, 'admin/commerce/orders/' . $order_id . '/payment/' . $transaction_id .'/delete', array('query' => drupal_get_destination()));
    }
  }
}
