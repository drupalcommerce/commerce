<?php

/**
 * Filter by order state.
 */
class commerce_order_handler_filter_order_state extends views_handler_filter_in_operator {
  function get_value_options() {
    if (!isset($this->value_options)) {
      $this->value_title = t('State');
      $this->value_options = commerce_order_state_get_title();
    }
  }

  function op_simple() {
    if (empty($this->value)) {
      return;
    }

    $this->ensure_my_table();

    // For each specified state, build an array of order statuses to check for.
    $order_statuses = array();

    foreach ($this->value as $state) {
      $order_statuses += commerce_order_statuses(array('state' => $state));
    }

    $this->query->add_where($this->options['group'], "$this->table_alias.status", array_keys($order_statuses), $this->operator);
  }

  function op_empty() {
    $this->ensure_my_table();

    if ($this->operator == 'empty') {
      $operator = "IS NULL";
    }
    else {
      $operator = "IS NOT NULL";
    }

    $this->query->add_where($this->options['group'], "$this->table_alias.status", NULL, $operator);
  }
}
