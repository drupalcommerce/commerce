<?php

/**
 * Filter by a select group of product display node types.
 *
 * @ingroup views_filter_handlers
 */
class commerce_product_reference_handler_filter_node_type extends views_handler_filter_in_operator {
  function get_value_options() {
    if (!isset($this->value_options)) {
      $this->value_title = t('Product display content types');

      // Build an options array of product display content types only.
      $options = array();

      foreach (commerce_product_reference_node_types() as $type => $info) {
        $options[$type] = t($info->name);
      }

      asort($options);
      $this->value_options = $options;
    }
  }
}
