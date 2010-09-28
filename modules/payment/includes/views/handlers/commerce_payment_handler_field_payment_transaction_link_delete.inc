<?php
// $Id$

/**
 * Field handler to present a link to delete a payment transaction.
 */
class commerce_payment_handler_field_payment_transaction_link_delete extends commerce_payment_handler_field_payment_transaction_link {

  function render($values) {
    // Ensure the user has access to delete this payment transaction.
    if (user_access('administer payments')) {
      $text = !empty($this->options['text']) ? $this->options['text'] : t('delete');
      $transaction_id = $values->{$this->aliases['transaction_id']};
      $order_id = $values->{$this->aliases['order_id']};

      return l($text, 'admin/commerce/orders/' . $order_id . '/payment/' . $transaction_id .'/delete', array('query' => drupal_get_destination()));
    }
  }
}
