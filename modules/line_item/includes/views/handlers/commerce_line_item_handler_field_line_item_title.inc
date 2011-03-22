<?php

/**
 * Field handler to return a line item's title.
 */
class commerce_line_item_handler_field_line_item_title extends views_handler_field {
  function construct() {
    parent::construct();

    $this->additional_fields['line_item_id'] = 'line_item_id';
  }

  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  function render($values) {
    // Load the line item and return its title.
    $line_item_id = $this->get_value($values, 'line_item_id');
    $line_item = commerce_line_item_load($line_item_id);
    return commerce_line_item_title($line_item);
  }
}
