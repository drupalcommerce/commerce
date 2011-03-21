<?php

/**
 * Field handler to display a transaction message with variable replacement.
 */
class commerce_payment_handler_field_message extends views_handler_field {
  function init(&$view, &$options) {
    parent::init($view, $options);

    $this->additional_fields['message_variables'] = 'message_variables';
  }

  function render($values) {
    $variables = $this->get_value($values, 'message_variables');
    $variables = unserialize($variables);
    $value = $this->get_value($values);

    return t($value, is_array($variables) ? $variables : array());
  }
}
