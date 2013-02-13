<?php

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
    'field' => 'product_id',
    'title' => t('Commerce Product'),
    'help' => t('Products from the store.'),
    'access query tag' => 'commerce_product_access',
  );
  $data['commerce_product']['table']['entity type'] = 'commerce_product';

  // Expose the product ID.
  $data['commerce_product']['product_id'] = array(
    'title' => t('Product ID'),
    'help' => t('The unique internal identifier of the product.'),
    'field' => array(
      'handler' => 'commerce_product_handler_field_product',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'commerce_product_handler_argument_product_id',
    ),
  );

  // Expose the product SKU.
  $data['commerce_product']['sku'] = array(
    'title' => t('SKU'),
    'help' => t('The unique human-readable identifier of the product.'),
    'field' => array(
      'handler' => 'commerce_product_handler_field_product',
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
      'handler' => 'commerce_product_handler_field_product_type',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'commerce_product_handler_filter_product_type',
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
      'handler' => 'commerce_product_handler_field_product',
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

  if (module_exists('locale')) {
    // Expose the language
    $data['commerce_product']['language'] = array(
      'title' => t('Language'),
      'help' => t('The language the product is in.'),
      'field' => array(
       'handler' => 'views_handler_field_locale_language',
       'click sortable' => TRUE,
      ),
     'filter' => array(
       'handler' => 'views_handler_filter_locale_language',
     ),
     'argument' => array(
       'handler' => 'views_handler_argument_locale_language',
     ),
     'sort' => array(
     'handler' => 'views_handler_sort',
     ),
    );
  }

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
      'output formats' => array(
        'active-disabled' => array(t('Active'), t('Disabled')),
      ),
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

  $data['commerce_product']['created_fulldate'] = array(
    'title' => t('Created date'),
    'help' => t('In the form of CCYYMMDD.'),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_node_created_fulldate',
    ),
  );

  $data['commerce_product']['created_year_month'] = array(
    'title' => t('Created year + month'),
    'help' => t('In the form of YYYYMM.'),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_node_created_year_month',
    ),
  );

  $data['commerce_product']['created_timestamp_year'] = array(
    'title' => t('Created year'),
    'help' => t('In the form of YYYY.'),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_node_created_year',
    ),
  );

  $data['commerce_product']['created_month'] = array(
    'title' => t('Created month'),
    'help' => t('In the form of MM (01 - 12).'),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_node_created_month',
    ),
  );

  $data['commerce_product']['created_day'] = array(
    'title' => t('Created day'),
    'help' => t('In the form of DD (01 - 31).'),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_node_created_day',
    ),
  );

  $data['commerce_product']['created_week'] = array(
    'title' => t('Created week'),
    'help' => t('In the form of WW (01 - 53).'),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_node_created_week',
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

  $data['commerce_product']['changed_fulldate'] = array(
    'title' => t('Updated date'),
    'help' => t('In the form of CCYYMMDD.'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_fulldate',
    ),
  );

  $data['commerce_product']['changed_year_month'] = array(
    'title' => t('Updated year + month'),
    'help' => t('In the form of YYYYMM.'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_year_month',
    ),
  );

  $data['commerce_product']['changed_timestamp_year'] = array(
    'title' => t('Updated year'),
    'help' => t('In the form of YYYY.'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_year',
    ),
  );

  $data['commerce_product']['changed_month'] = array(
    'title' => t('Updated month'),
    'help' => t('In the form of MM (01 - 12).'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_month',
    ),
  );

  $data['commerce_product']['changed_day'] = array(
    'title' => t('Updated day'),
    'help' => t('In the form of DD (01 - 31).'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_day',
    ),
  );

  $data['commerce_product']['changed_week'] = array(
    'title' => t('Updated week'),
    'help' => t('In the form of WW (01 - 53).'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_week',
    ),
  );

  // Expose links to operate on the product.
  $data['commerce_product']['view_product'] = array(
    'field' => array(
      'title' => t('Link'),
      'help' => t('Provide a simple link to the administrator view of the product.'),
      'handler' => 'commerce_product_handler_field_product_link',
    ),
  );
  $data['commerce_product']['edit_product'] = array(
    'field' => array(
      'title' => t('Edit link'),
      'help' => t('Provide a simple link to edit the product.'),
      'handler' => 'commerce_product_handler_field_product_link_edit',
    ),
  );
  $data['commerce_product']['delete_product'] = array(
    'field' => array(
      'title' => t('Delete link'),
      'help' => t('Provide a simple link to delete the product.'),
      'handler' => 'commerce_product_handler_field_product_link_delete',
    ),
  );

  $data['commerce_product']['operations'] = array(
    'field' => array(
      'title' => t('Operations links'),
      'help' => t('Display all the available operations links for the product.'),
      'handler' => 'commerce_product_handler_field_product_operations',
    ),
  );

  $data['commerce_product']['empty_text'] = array(
    'title' => t('Empty text'),
    'help' => t('Displays an appropriate empty text message for product lists.'),
    'area' => array(
      'handler' => 'commerce_product_handler_area_empty_text',
    ),
  );

  $data['commerce_product_revision']['table']['group']  = t('Commerce Product Revision');

  // Advertise this table as a possible base table
  $data['commerce_product_revision']['table']['base'] = array(
    'field' => 'revision_id',
    'title' => t('Commerce Product revision'),
    'help' => t('Commerce Product revision is a history of changes to a product.'),
  );

  $data['commerce_product_revision']['table']['join'] = array(
    'commerce_product' => array(
      'left_field' => 'product_id',
      'field' => 'product_id',
    )
  );

  // Expose the revision product ID
  $data['commerce_product_revision']['product_id'] = array(
    'title' => t('Product ID'),
    'help' => t('The unique internal identifier of the product.'),
    'field' => array(
      'handler' => 'commerce_product_handler_field_product',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'commerce_product_handler_argument_product_id',
    ),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'commerce_product',
      'base field' => 'product_id',
      'title' => t('Product'),
      'label' => t('Relate this revision to its product.'),
    ),
  );

  // Expose the revision id.
  $data['commerce_product_revision']['revision_id'] = array(
    'title' => t('Revision ID'),
    'help' => t('The revision ID of the product revision.'),
    'field' => array(
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // Expose the product revision SKU.
  $data['commerce_product_revision']['sku'] = array(
    'title' => t('SKU'),
    'help' => t('The unique human-readable identifier of the product revision.'),
    'field' => array(
      'handler' => 'commerce_product_handler_field_product',
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

  // Expose the product revision title.
  $data['commerce_product_revision']['title'] = array(
    'title' => t('Title'),
    'help' => t('The title of the product revision used for administrative display.'),
    'field' => array(
      'handler' => 'commerce_product_handler_field_product',
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

  // Expose the product revision user ID
  $data['commerce_product_revision']['revision_uid'] = array(
    'title' => t('User'),
    'help' => t('Relate a product revision to the user who created the revision.'),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'users',
      'base field' => 'uid',
      'field' => 'revision_uid',
      'field_name' => 'revision_uid',
      'label' => t('Revision user'),
    ),
  );

  // Expose the product revision status.
  $data['commerce_product_revision']['status'] = array(
    'title' => t('Status'),
    'help' => t('Whether or not the product was active at the time of the revision.'),
    'field' => array(
      'handler' => 'views_handler_field_boolean',
      'click sortable' => TRUE,
      'output formats' => array(
        'active-disabled' => array(t('Active'), t('Disabled')),
      ),
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

  // Expose the order revision log.
  $data['commerce_product_revision']['log'] = array(
    'title' => t('Log message'),
    'help' => t('The log message entered when the revision was created.'),
    'field' => array(
      'handler' => 'views_handler_field_xss',
     ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
  );

  // Expose the revision timestamp.
  $data['commerce_product_revision']['revision_timestamp'] = array(
    'title' => t('Revision Timestamp'),
    'help' => t('The date the order revision was created.'),
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

  return $data;
}
