<?php

/**
 * Filter by line item type
 */
class commerce_line_item_handler_filter_line_item_type extends views_handler_filter_in_operator {
  function get_value_options() {
    if (!isset($this->value_options)) {
      $this->value_title = t('Line item type');

      foreach (commerce_line_item_type_get_name() as $type => $name) {
        $options[$type] = $name;
      }
      $this->value_options = $options;
    }
  }
}
