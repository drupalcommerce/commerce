<?php
// $Id$

/**
 * Export Drupal Commerce products to Views.
 */

/**
 * Implements hook_views_data()
 */
function commerce_product_views_data() {
  $data['commerce_product']['table']['group']  = t('Commerce: product');

  $data['commerce_product']['table']['base'] = array(
    'field' => 'product_sku',
    'title' => t('Commerce: product'),
    'help' => t('Products from the store.'),
  );

  // Product SKU.
  $data['commerce_product']['product_sku'] = array(
    'title' => t('Product SKU'),
    'help' => t('The unique identifier of the product.'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );

  return $data;
}
