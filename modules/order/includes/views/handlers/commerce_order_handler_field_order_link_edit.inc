<?php
// $Id$

/**
 * Field handler to present an order edit link.
 */
class commerce_order_handler_field_order_link_edit extends commerce_order_handler_field_order_link {
  function construct() {
    parent::construct();

    $this->additional_fields['uid'] = 'uid';
  }

  function render($values) {
    // Ensure the user has access to edit this order.
    $order = commerce_order_new();
    $order->order_id = $values->{$this->aliases['order_id']};
    $order->uid = $values->{$this->aliases['uid']};

    if (!commerce_order_access('update', $order)) {
      return;
    }

    $text = !empty($this->options['text']) ? $this->options['text'] : t('edit');

    return l($text, 'admin/commerce/orders/' . $order->order_id . '/edit', array('query' => drupal_get_destination()));
  }
}
