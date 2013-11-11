<?php

/**
 * Field handler to present a link to delete an order.
 */
class commerce_order_handler_field_order_link_delete extends commerce_order_handler_field_order_link {
  function construct() {
    parent::construct();

    $this->additional_fields['uid'] = 'uid';
  }

  function render($values) {
    // Ensure the user has access to delete this order.
    $order = commerce_order_new();
    $order->order_id = $this->get_value($values, 'order_id');
    $order->uid = $this->get_value($values, 'uid');

    if (!commerce_order_access('delete', $order)) {
      return;
    }

    $text = !empty($this->options['text']) ? $this->options['text'] : t('delete');

    return l($text, 'admin/commerce/orders/' . $order->order_id . '/delete', array('query' => drupal_get_destination()));
  }
}
