<?php

/**
 * Field handler to be able to show the balance order with currency.
 */
class commerce_payment_handler_field_balance extends views_handler_field {
  function init(&$view, &$options) {
    parent::init($view, $options);

    $this->additional_fields['order_id'] = 'order_id';
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

  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  function render($values) {
    $order_id = $this->get_value($values, 'order_id');

    // Only render this field if we find a valid order.
    if (!empty($order_id) && $order = commerce_order_load($order_id)) {
      $balance = commerce_payment_order_balance($order);

      // Output according to the format selected as with price fields.
      switch ($this->options['display_format']) {
        case 'formatted':
          return commerce_currency_format($balance['amount'], $balance['currency_code']);
        case 'raw':
          return check_plain($balance['amount']);
      }
    }
  }
}
