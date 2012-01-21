<?php

/**
 * Filter handler to convert price filter to use decimal + currency.
 */
class commerce_price_handler_filter_commerce_price_amount extends views_handler_filter_numeric {
  function operators() {
    $operators = parent::operators();

    // Remove the regular expression operator.
    unset($operators['regular_expression']);

    return $operators;
  }

  function option_definition(){
    $options = parent::option_definition();

    $options['currency'] = array('default' => commerce_default_currency());

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['currency'] = array(
      '#type' => 'select',
      '#title' => t('Currency'),
      '#description' => t('Pick a currency to use for this filter.'),
      '#options' => commerce_currency_code_options_list(),
      '#default_value' => $this->options['currency'],
    );
  }

  function query() {
    // Convert user input to a price amount based on selected currency.
    foreach ($this->value as $key => $value) {
      if ($value) {
        $this->value[$key] = commerce_currency_decimal_to_amount($value, $this->options['currency']);
      }
    }

    parent::query();
  }
}
