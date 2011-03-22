<?php

/**
 * Field handler to present a product's operations links.
 */
class commerce_product_handler_field_product_operations extends views_handler_field {
  function construct() {
    parent::construct();

    $this->additional_fields['product_id'] = 'product_id';
  }

  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  function render($values) {
    $product_id = $this->get_value($values, 'product_id');

    $links = menu_contextual_links('commerce-product', 'admin/commerce/products', array($product_id));

    if (!empty($links)) {
      drupal_add_css(drupal_get_path('module', 'commerce_product') . '/theme/commerce_product_views.css');

      return theme('links', array('links' => $links, 'attributes' => array('class' => array('links', 'inline', 'operations'))));
    }
  }
}
