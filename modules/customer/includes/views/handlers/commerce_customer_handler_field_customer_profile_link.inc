<?php

/**
 * Field handler to present a link to a customer profile.
 */
class commerce_customer_handler_field_customer_profile_link extends views_handler_field {
  function construct() {
    parent::construct();

    $this->additional_fields['profile_id'] = 'profile_id';
  }

  function option_definition() {
    $options = parent::option_definition();

    $options['text'] = array('default' => '', 'translatable' => TRUE);

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['text'] = array(
      '#type' => 'textfield',
      '#title' => t('Text to display'),
      '#default_value' => $this->options['text'],
    );
  }

  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  function render($values) {
    $text = !empty($this->options['text']) ? $this->options['text'] : t('view');
    $profile_id = $this->get_value($values, 'profile_id');

    return l($text, 'admin/commerce/customer-profiles/' . $profile_id);
  }
}
