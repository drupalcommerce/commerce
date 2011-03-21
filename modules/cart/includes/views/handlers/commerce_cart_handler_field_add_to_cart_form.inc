<?php

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
    $options['display_path'] = array('default' => FALSE);

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

    $form['display_path'] = array(
      '#type' => 'checkbox',
      '#title' => t("Link products added to the cart from this View to this View's path if displayed as a page."),
      '#default_value' => $this->options['display_path'],
    );
  }

  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  function render($values) {
    $product_id = $this->get_value($values, 'product_id');
    if ($product_id) {
      $product_ids = array($product_id);
      $default_quantity = $this->options['default_quantity'] <= 0 ? 1 : $this->options['default_quantity'];

      // Generate a form ID for this add to cart form.
      $form_id = commerce_cart_add_to_cart_form_id($product_ids, $default_quantity);

      // Generate a display URI array if this field is displayed on a page View.
      if ($this->view->display[$this->view->current_display]->display_plugin == 'page' &&
        $this->options['display_path']) {
        // Use the display options array as it contains the required path key.
        $display_uri = $this->view->display[$this->view->current_display]->display_options;
      }
      else {
        $display_uri = NULL;
      }

      $form = drupal_get_form($form_id, $product_ids, $this->options['show_quantity'], $default_quantity, array(), $display_uri);

      return drupal_render($form);
    }
  }
}
