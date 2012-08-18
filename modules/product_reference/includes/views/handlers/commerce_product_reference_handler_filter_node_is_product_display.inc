<?php

/**
 * Filter by whether or not a node is a product display.
 *
 * @ingroup views_filter_handlers
 */
class commerce_product_reference_handler_filter_node_is_product_display extends views_handler_filter_boolean_operator {
  function can_expose() { return FALSE; }

  function query() {
    $this->ensure_my_table();
    $this->query->add_where($this->options['group'], "$this->table_alias.$this->real_field", array_keys(commerce_product_reference_node_types()), $this->value ? 'IN' : 'NOT IN');
  }

  function ensure_table($table, $relationship) {
    return 'node';
  }
}
