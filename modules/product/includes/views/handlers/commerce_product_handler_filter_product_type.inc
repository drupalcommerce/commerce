<?php

/**
 * Filter by product type.
 */
class commerce_product_handler_filter_product_type extends views_handler_filter_in_operator {
  // Display a list of product types in the filter's options.
  function get_value_options() {
    if (!isset($this->value_options)) {
      $this->value_title = t('Product type');
      $this->value_options = commerce_product_type_get_name();
    }
  }
}
