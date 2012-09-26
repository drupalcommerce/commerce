<?php

/**
 * Filter by order status.
 */
class commerce_order_handler_filter_order_status extends views_handler_filter_in_operator {
  function get_value_options() {
    if (!isset($this->value_options)) {
      $this->value_title = t('Status');
      $this->value_options = array();
      foreach (commerce_order_state_get_title() as $name => $title) {
        foreach (commerce_order_statuses(array('state' => $name)) as $order_status) {
          $this->value_options[$order_status['name']] = $order_status['title'] . ' (' . $title . ')';
        }
      }
    }
  }
}
