<?php

/**
 * Field handler to translate a payment method ID into its readable form.
 */
class commerce_payment_handler_field_payment_method extends views_handler_field {
  function option_definition() {
    $options = parent::option_definition();

    $options['title'] = array('default' => 'title');

    return $options;
  }

  /**
   * Provide the link to order option.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['title'] = array(
      '#title' => t('Display the payment method using the following title'),
      '#type' => 'radios',
      '#options' => array(
        'title' => t('The full administrative title of the payment method'),
        'short_title' => t('A short version of the title safe to display to all'),
        'display_title' => t('The title displayed on the checkout form (may include HTML)'),
      ),
      '#default_value' => $this->options['title'],
    );
  }

  function render($values) {
    $value = $this->get_value($values);
    if ($payment_method = commerce_payment_method_load($value)) {
      return $this->sanitize_value($payment_method[$this->options['title']]);
    }
    else {
      return t('Unknown');
    }
  }
}
