<?php

/**
 * Field handler to translate an order type into its readable form.
 */
class commerce_order_handler_field_order_type extends commerce_order_handler_field_order {
  function render($values) {
    $type = $this->get_value($values);
    if ($type) {
      return commerce_order_type_get_name($type);
    }
  }
}
