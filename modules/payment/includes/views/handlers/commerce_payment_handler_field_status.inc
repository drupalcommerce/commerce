<?php

/**
 * Field handler for the payment transaction status.
 */
class commerce_payment_handler_field_status extends views_handler_field {

  function option_definition() {
    $options = parent::option_definition();

    $options['display_style'] = array('default' => 'icon');

    return $options;
  }

  /**
   * Provide the checkbox for enabling the Add payment form..
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['display_style'] = array(
      '#type' => 'select',
      '#title' => t('Display style'),
      '#options' => array(
        'icon' => t('Status icon'),
        'title' => t('Status title'),
        'raw' => t('Raw status'),
      ),
      '#default_value' => $this->options['display_style'],
    );
  }

  function render($values) {
    $status = $this->get_value($values);
    $transaction_status = commerce_payment_transaction_status_load($status);

    if (!$transaction_status) {
      return '?';
    }

    $variables = array(
      'transaction_status' => $transaction_status,
    );

    switch ($this->options['display_style']) {
      case 'icon':
        return theme('commerce_payment_transaction_status_icon', $variables);

      case 'title':
        return theme('commerce_payment_transaction_status_text', $variables + array('text' => $this->sanitize_value($transaction_status['title'])));

      case 'raw':
        return theme('commerce_payment_transaction_status_text', $variables + array('text' => $this->sanitize_value($transaction_status['status'])));
    }
  }
}
