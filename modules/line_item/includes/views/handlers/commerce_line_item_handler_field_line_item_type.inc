<?php

/**
 * Field handler to translate a line item type into its readable form.
 */
class commerce_line_item_handler_field_line_item_type extends views_handler_field {
  function render($values) {
    $type = $this->get_value($values);
    $value = commerce_line_item_type_get_name($type);

    return $this->sanitize_value($value);
  }
}
