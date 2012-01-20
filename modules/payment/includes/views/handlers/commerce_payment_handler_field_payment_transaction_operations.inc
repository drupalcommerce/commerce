<?php

/**
 * Field handler to present a payment transaction's operations links.
 */
class commerce_payment_handler_field_payment_transaction_operations extends views_handler_field {
  function construct() {
    parent::construct();

    $this->additional_fields['transaction_id'] = 'transaction_id';
    $this->additional_fields['order_id'] = 'order_id';
  }

  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  function render($values) {
    $transaction_id = $this->get_value($values, 'transaction_id');
    $order_id = $this->get_value($values, 'order_id');

    $links = menu_contextual_links('commerce-payment-transaction', 'admin/commerce/orders/' . $order_id . '/payment', array($transaction_id));

    if (!empty($links)) {
      drupal_add_css(drupal_get_path('module', 'commerce_payment') . '/theme/commerce_payment.admin.css');
      return theme('links', array('links' => $links, 'attributes' => array('class' => array('links', 'inline', 'operations'))));
    }
  }
}
