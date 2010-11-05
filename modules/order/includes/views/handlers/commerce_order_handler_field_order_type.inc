<?php
// $Id$

/**
 * Field handler to translate an order type into its readable form.
 */
class commerce_order_handler_field_order_type extends commerce_order_handler_field_order {
  function render($values) {
    if (!empty($values->{$this->field_alias})) {
      return commerce_order_type_get_name($values->{$this->field_alias});
    }
  }
}
