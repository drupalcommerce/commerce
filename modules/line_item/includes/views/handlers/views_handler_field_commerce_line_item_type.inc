<?php
// $Id$

/**
 * Field handler to translate a product type into its readable form.
 */
class views_handler_field_commerce_line_item_type extends views_handler_field {
  function render($values) {
    $value = commerce_line_item_type_get_name($values->{$this->field_alias});

    return check_plain($value);
  }
}
