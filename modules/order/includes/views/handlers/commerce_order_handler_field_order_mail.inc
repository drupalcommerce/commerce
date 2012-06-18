<?php

/**
 * Field handler to display an order e-mail address.
 */
class commerce_order_handler_field_order_mail extends views_handler_field {

  function option_definition() {
    $options = parent::option_definition();

    $options['render_as_link'] = array('default' => FALSE);

    return $options;
  }

  /**
   * Provide the mailto link option.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['render_as_link'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display the e-mail address as a mailto link'),
      '#default_value' => $this->options['render_as_link'],
    );
  }

  function render($values) {
    if (!empty($this->options['render_as_link'])) {
      $value = $this->get_value($values);
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = "mailto:" . $this->sanitize_value($value);
    }
    return parent::render($values);
  }
}
