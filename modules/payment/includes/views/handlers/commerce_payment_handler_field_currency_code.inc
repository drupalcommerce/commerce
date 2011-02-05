<?php
// $Id$

/**
 * @file
 * Contains the basic currency code field handler.
 */

/**
 * Field handler to allow rendering of the currency code in different formats.
 */
class commerce_payment_handler_field_currency_code extends views_handler_field {
  function option_definition() {
    $options = parent::option_definition();

    $options['display_format'] = array('default' => 'code');

    return $options;
  }

  /**
   * Provide the currency code format option.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['display_format'] = array(
      '#title' => t('Display format'),
      '#type' => 'select',
      '#options' => array(
        'code' => t('Three letter code'),
        'numeric_code' => t('Numeric code'),
        'name' => t('Currency name'),
        'symbol' => t('Currency symbol'),
      ),
      '#default_value' => $this->options['display_format'],
    );
  }

  function render($values) {
    $currency = commerce_currency_load($values->{$this->field_alias});

    return check_plain($currency[$this->options['display_format']]);
  }
}
