<?php

/**
 * Field handler to present a link to an order.
 */
class commerce_order_handler_field_order_link extends views_handler_field {
  function construct() {
    parent::construct();

    $this->additional_fields['order_id'] = 'order_id';
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
    $order_id = $this->get_value($values, 'order_id');

    return l($text, 'admin/commerce/orders/' . $order_id, array('query' => drupal_get_destination()));
  }
}
