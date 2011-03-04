<?php

/**
 * Filter by currency code.
 */
class commerce_payment_handler_filter_currency_code extends views_handler_filter_in_operator {
  var $value_form_type = 'select';
  function get_value_options() {
    if (!isset($this->value_options)) {
      $this->value_title = t('Currency Code');
      $currencies = commerce_currencies();
      foreach ($currencies as $currency => $info) {
        $options[$currency] = t('@code - !name - @symbol', array('@code' => $info['code'], '@symbol' => $info['symbol'], '!name' => $info['name']));
      }
      $this->value_options = $options;
    }
  }
}
