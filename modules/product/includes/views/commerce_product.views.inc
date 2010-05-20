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
    'field' => 'sku',
    'title' => t('Commerce Product'),
    'help' => t('Products from the store.'),
  );

  // Expose the product ID.
  $data['commerce_product']['product_id'] = array(
    'title' => t('Product ID'),
    'help' => t('The unique internal identifier of the product.'),
    'field' => array(
      'handler' => 'views_handler_field_commerce_product',
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
  $data['commerce_product']['sku'] = array(
    'title' => t('SKU'),
    'help' => t('The unique human-readable identifier of the product.'),
    'field' => array(
      'handler' => 'views_handler_field_commerce_product',
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

  // Expose the product type.
  $data['commerce_product']['type'] = array(
    'title' => t('Type'),
    'help' => t('The human-readable name of the type of the product.'),
    'field' => array(
      'handler' => 'views_handler_field_commerce_product_type',
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
      'handler' => 'views_handler_field_commerce_product',
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

  // Expose the creator uid.
  $data['commerce_product']['uid'] = array(
    'title' => t('Creator'),
    'help' => t('Relate a product to the user who created it.'),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'users',
      'field' => 'uid',
      'label' => t('Product creator'),
    ),
  );

  // Expose the product status.
  $data['commerce_product']['status'] = array(
    'title' => t('Status'),
    'help' => t('Whether or not the product is active.'),
    'field' => array(
      'handler' => 'views_handler_field_boolean',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
      'label' => t('Active'),
      'type' => 'yes-no',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // Expose the created and changed timestamps.
  $data['commerce_product']['created'] = array(
    'title' => t('Created date'),
    'help' => t('The date the product was created.'),
    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
  );

  $data['commerce_product']['changed'] = array(
    'title' => t('Updated date'),
    'help' => t('The date the product was last updated.'),
    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
  );

  // Expose links to operate on the product.
  $data['commerce_product']['edit_product'] = array(
    'field' => array(
      'title' => t('Edit link'),
      'help' => t('Provide a simple link to edit the product.'),
      'handler' => 'views_handler_field_commerce_product_link_edit',
    ),
  );

  $data['commerce_product']['delete_product'] = array(
    'field' => array(
      'title' => t('Delete link'),
      'help' => t('Provide a simple link to delete the product.'),
      'handler' => 'views_handler_field_commerce_product_link_delete',
    ),
  );

  return $data;
}

/**
 * Implements hook_views_handlers().
 */
function commerce_product_views_handlers() {
  return array(
    'info' => array(
      'path' => drupal_get_path('module', 'commerce_product') . '/includes/views/handlers',
    ),
    'handlers' => array(
      // Field handlers
      'views_handler_field_commerce_product' => array(
        'parent' => 'views_handler_field',
      ),
      'views_handler_field_commerce_product_link' => array(
        'parent' => 'views_handler_field',
      ),
      'views_handler_field_commerce_product_type' => array(
        'parent' => 'views_handler_field_commerce_product',
      ),
      'views_handler_field_commerce_product_link_edit' => array(
        'parent' => 'views_handler_field_commerce_product_link',
      ),
      'views_handler_field_commerce_product_link_delete' => array(
        'parent' => 'views_handler_field_commerce_product_link',
      ),
    ),
  );
}
