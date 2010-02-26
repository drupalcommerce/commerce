<?php
// $Id$

/**
 * Field handler to translate a product type into its readable form.
 */
class views_handler_field_commerce_product_type extends views_handler_field_commerce_product {
  function render($values) {
    $value = commerce_product_type_get_name($values->{$this->field_alias});

    return $this->render_link((check_plain($value)), $values);
  }
}
