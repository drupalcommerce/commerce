<?php
// $Id$

/**
 * @file
 * Contains the basic amount field handler.
 */

/**
 * Field handler to allow rendering of the amount using currency formatting.
 */
class commerce_payment_handler_field_amount extends views_handler_field {
  function init(&$view, &$options) {
    parent::init($view, $options);

    $this->additional_fields['currency_code'] = 'currency_code';
  }

  function option_definition() {
    $options = parent::option_definition();

    $options['display_format'] = array('default' => 'formatted');

    return $options;
  }

  /**
   * Provide the currency format option.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['display_format'] = array(
      '#type' => 'select',
      '#title' => t('Display format'),
      '#options' => array(
        'formatted' => t('Currency formatted amount'),
        'raw' => t('Raw amount'),
      ),
      '#default_value' => $this->options['display_format'],
    );
  }

  /**
   * Convert the amount integer to a price decimal amount.
   */
  function pre_render(&$values) {
    foreach ($values as $key => &$value) {
      $value->{$this->field_alias} = commerce_currency_integer_to_amount($value->{$this->field_alias}, $value->{$this->aliases['currency_code']});
    }
  }

  function render($values) {
    switch ($this->options['display_format']) {
      case 'formatted':
        return commerce_currency_format($values->{$this->field_alias}, $values->{$this->aliases['currency_code']});

      case 'raw':
        // First load the full currency array.
        $currency = commerce_currency_load($values->{$this->aliases['currency_code']});

        // Format the price as a number.
        return number_format(commerce_currency_round($values->{$this->field_alias}, $currency), $currency['decimals']);
    }
  }
}
