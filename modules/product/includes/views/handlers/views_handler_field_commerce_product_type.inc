<?php
// $Id$

/**
 * Field handler to translate a product type into its readable form.
 */
class views_handler_field_commerce_product_type extends views_handler_field_commerce_product {
  function render($values) {
    $product_types = commerce_product_types();
    $value = $product_types[$values->{$this->field_alias}]['name'];

    return $this->render_link((check_plain($value)), $values);
  }
}
