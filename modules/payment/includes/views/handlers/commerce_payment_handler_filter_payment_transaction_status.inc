<?php

/**
 * Filter by payment transaction status.
 */
class commerce_payment_handler_filter_payment_transaction_status extends views_handler_filter_in_operator {
  function get_value_options() {
    if (!isset($this->value_options)) {
      $this->value_title = t('Payment Status');
      $statuses = commerce_payment_transaction_statuses();
      foreach ($statuses as $status => $info) {
        $options[$status] = t($info['title']);
      }
      $this->value_options = $options;
    }
  }
}
