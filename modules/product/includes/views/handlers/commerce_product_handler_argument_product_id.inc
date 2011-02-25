<?php

/**
 * Argument handler to display product titles in View using product arguments.
 */
class commerce_product_handler_argument_product_id extends views_handler_argument_numeric {
  function title_query() {
    $titles = array();
    $result = db_select('commerce_product', 'cp')
      ->fields('cp', array('title'))
      ->condition('cp.product_id', $this->value)
      ->execute();
    foreach ($result as $product) {
      $titles[] = check_plain($product->title);
    }
    return $titles;
  }
}
