<?php

/**
 * Field handler to present an order's operations links.
 */
class commerce_order_handler_field_order_operations extends views_handler_field {
  function construct() {
    parent::construct();

    $this->additional_fields['order_id'] = 'order_id';
  }

  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  function render($values) {
    $order_id = $this->get_value($values, 'order_id');
    $links = menu_contextual_links('commerce-order', 'admin/commerce/orders', array($order_id));

    if (!empty($links)) {
      drupal_add_css(drupal_get_path('module', 'commerce_order') . '/theme/commerce_order.admin.css');
      return theme('links', array('links' => $links, 'attributes' => array('class' => array('links', 'inline', 'operations'))));
    }
  }
}
