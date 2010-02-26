<?php
// $Id$

/**
 * Export Drupal Commerce products to Views.
 */

/**
 * Implements hook_views_data()
 */
function commerce_product_views_data() {
  $data = array();

  $data['commerce_product']['table']['group']  = t('Commerce Product');

  $data['commerce_product']['table']['base'] = array(
    'field' => 'product_sku',
    'title' => t('Commerce Product'),
    'help' => t('Products from the store.'),
  );

  // Expose the product ID.
  $data['commerce_product']['product_id'] = array(
    'title' => t('Product ID'),
    'help' => t('The unique internal identifier of the product.'),
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

  // Expose the product SKU.
  $data['commerce_product']['product_sku'] = array(
    'title' => t('SKU'),
    'help' => t('The unique human readable identifier of the product.'),
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

  // Expose the product title.
  $data['commerce_product']['title'] = array(
    'title' => t('Title'),
    'help' => t('The title of the product used for administrative display.'),
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

/**
 * Implements hook_views_handlers().
 */
function commerce_product_views_handlers() {
  return array(
    // field handlers
    'views_handler_field_commerce_product_type',
    'views_handler_field_commerce_product_link_edit',
    'views_handler_field_commerce_product_link_delete',
  );
}
