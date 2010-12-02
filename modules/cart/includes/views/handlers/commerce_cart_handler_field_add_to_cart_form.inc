<?php
// $Id$

/**
 * Field handler to present an add to cart form for the product..
 */
class commerce_cart_handler_field_add_to_cart_form extends views_handler_field {
  function construct() {
    parent::construct();

    $this->additional_fields['product_id'] = 'product_id';
  }

  function option_definition() {
    $options = parent::option_definition();

    $options['show_quantity'] = array('default' => FALSE);
    $options['default_quantity'] = array('default' => 1);

    return $options;
  }

  /**
   * Provide the add to cart display options.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['show_quantity'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display a textfield quantity widget on the add to cart form.'),
      '#default_value' => $this->options['show_quantity'],
    );

    $form['default_quantity'] = array(
      '#type' => 'textfield',
      '#title' => t('Default quantity'),
      '#default_value' => $this->options['default_quantity'] <= 0 ? 1 : $this->options['default_quantity'],
      '#element_validate' => array('commerce_cart_field_formatter_settings_form_quantity_validate'),
      '#size' => 16,
    );
  }

  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  function render($values) {
    if (!empty($values->{$this->aliases['product_id']})) {
      $product_ids = array($values->{$this->aliases['product_id']});
      $default_quantity = $this->options['default_quantity'] <= 0 ? 1 : $this->options['default_quantity'];

      // Generate a form ID for this add to cart form.
      $form_id = commerce_cart_add_to_cart_form_id($product_ids, $default_quantity);

      return drupal_render(drupal_get_form($form_id, $product_ids, $this->options['show_quantity'], $default_quantity));
    }
  }
}
