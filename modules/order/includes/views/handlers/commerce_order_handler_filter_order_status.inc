<?php

/**
 * Filter by order status.
 */
class commerce_order_handler_filter_order_status extends views_handler_filter_in_operator {
  function get_value_options() {
    if (!isset($this->value_options)) {
      $this->value_title = t('Status');
      $this->value_options = commerce_order_status_get_title();
    }
  }
}
