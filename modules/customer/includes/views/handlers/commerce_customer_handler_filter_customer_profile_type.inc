<?php

/**
 * Filter by customer profile type.
 */
class commerce_customer_handler_filter_customer_profile_type extends views_handler_filter_in_operator {
  function get_value_options() {
    if (!isset($this->value_options)) {
      $this->value_title = t('Customer profile type');
      $types = commerce_customer_profile_types();
      foreach ($types as $type => $info) {
        $options[$type] = t($info['name']);
      }
      $this->value_options = $options;
    }
  }
}
