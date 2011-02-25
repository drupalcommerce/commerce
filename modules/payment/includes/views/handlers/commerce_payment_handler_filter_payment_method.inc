<?php

/**
 * Filter by payment method.
 */
class commerce_payment_handler_filter_payment_method extends views_handler_filter_in_operator {
  function get_value_options() {
    if (!isset($this->value_options)) {
      $this->value_title = t('Payment Method');
      $methods = commerce_payment_methods();
      foreach ($methods as $method => $info) {
        $options[$method] = t($info['title']);
      }
      $this->value_options = $options;
    }
  }
}
