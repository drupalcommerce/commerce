<?php

/**
 * Field handler to translate an order status into its readable form.
 */
class commerce_order_handler_field_order_status extends commerce_order_handler_field_order {
  function render($values) {
    $name = $this->get_value($values);
    $value = commerce_order_status_get_title($name);

    // Only attempt to render a valid order status.
    if (!is_array($value)) {
      return $this->render_link($this->sanitize_value($value), $values);
    }
  }
}
