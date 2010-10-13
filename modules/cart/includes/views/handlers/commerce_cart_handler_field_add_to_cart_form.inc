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

  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  function render($values) {
    if (!empty($values->{$this->aliases['product_id']})) {
      $product_ids = array($values->{$this->aliases['product_id']});

      // Generate a form ID for this add to cart form.
      $form_id = commerce_cart_add_to_cart_form_id($product_ids);

      return drupal_render(drupal_get_form($form_id, $product_ids));
    }
  }
}
