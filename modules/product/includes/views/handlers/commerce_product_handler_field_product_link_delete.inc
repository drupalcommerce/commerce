<?php

/**
 * Field handler to present a link to delete a product.
 */
class commerce_product_handler_field_product_link_delete extends commerce_product_handler_field_product_link {
  function construct() {
    parent::construct();

    $this->additional_fields['type'] = 'type';
    $this->additional_fields['uid'] = 'uid';
  }

  function render($values) {
    // Ensure the user has access to delete this product.
    $product = commerce_product_new();
    $product->product_id = $this->get_value($values, 'product_id');
    $product->type = $this->get_value($values, 'type');
    $product->uid = $this->get_value($values, 'uid');

    if (!commerce_product_access('delete', $product)) {
      return;
    }

    $text = !empty($this->options['text']) ? $this->options['text'] : t('delete');

    return l($text, 'admin/commerce/products/' . $product->product_id . '/delete', array('query' => drupal_get_destination()));
  }
}
