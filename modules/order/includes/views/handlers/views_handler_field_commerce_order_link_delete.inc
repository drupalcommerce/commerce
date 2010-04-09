<?php
// $Id$

/**
 * Field handler to present a link to delete an order.
 */
class views_handler_field_commerce_order_link_delete extends views_handler_field_commerce_order_link {
  function construct() {
    parent::construct();

    $this->additional_fields['creator_uid'] = 'creator_uid';
  }

  function render($values) {
    // Ensure the user has access to delete this order.
    $order = commerce_order_new();
    $order->order_id = $values->{$this->aliases['order_id']};
    $order->creator_uid = $values->{$this->aliases['creator_uid']};

    if (!commerce_order_access('update', $order)) {
      return;
    }

    $text = !empty($this->options['text']) ? $this->options['text'] : t('delete');

    return l($text, 'admin/commerce/orders/' . $order->order_id . '/delete', array('query' => drupal_get_destination()));
  }
}
