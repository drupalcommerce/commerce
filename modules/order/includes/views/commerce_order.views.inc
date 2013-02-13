<?php

/**
 * Export Drupal Commerce orders to Views.
 */

/**
 * Implements hook_views_data()
 */
function commerce_order_views_data() {
  $data = array();

  $data['commerce_order']['table']['group']  = t('Commerce Order');

  $data['commerce_order']['table']['base'] = array(
    'field' => 'order_id',
    'title' => t('Commerce Order'),
    'help' => t('Order placed in the store.'),
    'access query tag' => 'commerce_order_access',
  );
  $data['commerce_order']['table']['entity type'] = 'commerce_order';

  $data['commerce_order']['table']['default_relationship'] = array(
    'commerce_order_revision' => array(
      'table' => 'commerce_order_revision',
      'field' => 'revision_id',
    ),
  );

  // Expose the order ID.
  $data['commerce_order']['order_id'] = array(
    'title' => t('Order ID', array(), array('context' => 'a drupal commerce order')),
    'help' => t('The unique internal identifier of the order.'),
    'field' => array(
      'handler' => 'commerce_order_handler_field_order',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'commerce_order_handler_argument_order_order_id',
      'name field' => 'order_number',
      'numeric' => TRUE,
      'validate type' => 'order_id',
    ),
  );

  // Expose the order number.
  $data['commerce_order']['order_number'] = array(
    'title' => t('Order number', array(), array('context' => 'a drupal commerce order')),
    'help' => t('The unique customer facing number of the order.'),
    'field' => array(
      'handler' => 'commerce_order_handler_field_order',
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

  // Expose the order type.
  $data['commerce_order']['type'] = array(
    'title' => t('Order type', array(), array('context' => 'a drupal commerce order')),
    'help' => t('The type of the order.'),
    'field' => array(
      'handler' => 'commerce_order_handler_field_order_type',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'commerce_order_handler_filter_order_type',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );

  // Expose the owner uid.
  $data['commerce_order']['uid'] = array(
    'title' => t('Uid'),
    'help' => t("The owner's user ID."),
    'field' => array(
      'handler' => 'views_handler_field_user',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_user_uid',
      'name field' => 'name', // display this field in the summary
    ),
    'filter' => array(
      'title' => t('Name'),
      'handler' => 'views_handler_filter_user_name',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'relationship' => array(
      'title' => t('Owner'),
      'help' => t("Relate this order to its owner's user account"),
      'handler' => 'views_handler_relationship',
      'base' => 'users',
      'base field' => 'uid',
      'field' => 'uid',
      'label' => t('Order owner'),
    ),
  );

  // Expose the order status.
  $data['commerce_order']['status'] = array(
    'title' => t('Order status'),
    'help' => t('The workflow status of the order.'),
    'field' => array(
      'handler' => 'commerce_order_handler_field_order_status',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'commerce_order_handler_filter_order_status',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );

  // Expose the order state.
  $data['commerce_order']['state'] = array(
    'title' => t('Order state'),
    'help' => t('The workflow state of the order.'),
    'field' => array(
      'handler' => 'commerce_order_handler_field_order_state',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'commerce_order_handler_filter_order_state',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );

  // Expose the order e-mail address.
  $data['commerce_order']['mail'] = array(
    'title' => t('E-mail'),
    'help' => t('The e-mail address used for correspondence about this order.'),
    'field' => array(
      'handler' => 'commerce_order_handler_field_order_mail',
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

  // Expose the created and changed timestamps.
  $data['commerce_order']['created'] = array(
    'title' => t('Created date'),
    'help' => t('The date the order was created.'),
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

  $data['commerce_order']['created_fulldate'] = array(
    'title' => t('Created date'),
    'help' => t('In the form of CCYYMMDD.'),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_node_created_fulldate',
    ),
  );

  $data['commerce_order']['created_year_month'] = array(
    'title' => t('Created year + month'),
    'help' => t('In the form of YYYYMM.'),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_node_created_year_month',
    ),
  );

  $data['commerce_order']['created_timestamp_year'] = array(
    'title' => t('Created year'),
    'help' => t('In the form of YYYY.'),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_node_created_year',
    ),
  );

  $data['commerce_order']['created_month'] = array(
    'title' => t('Created month'),
    'help' => t('In the form of MM (01 - 12).'),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_node_created_month',
    ),
  );

  $data['commerce_order']['created_day'] = array(
    'title' => t('Created day'),
    'help' => t('In the form of DD (01 - 31).'),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_node_created_day',
    ),
  );

  $data['commerce_order']['created_week'] = array(
    'title' => t('Created week'),
    'help' => t('In the form of WW (01 - 53).'),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_node_created_week',
    ),
  );

  $data['commerce_order']['changed'] = array(
    'title' => t('Updated date'),
    'help' => t('The date the order was last updated.'),
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

  $data['commerce_order']['changed_fulldate'] = array(
    'title' => t('Updated date'),
    'help' => t('In the form of CCYYMMDD.'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_fulldate',
    ),
  );

  $data['commerce_order']['changed_year_month'] = array(
    'title' => t('Updated year + month'),
    'help' => t('In the form of YYYYMM.'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_year_month',
    ),
  );

  $data['commerce_order']['changed_timestamp_year'] = array(
    'title' => t('Updated year'),
    'help' => t('In the form of YYYY.'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_year',
    ),
  );

  $data['commerce_order']['changed_month'] = array(
    'title' => t('Updated month'),
    'help' => t('In the form of MM (01 - 12).'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_month',
    ),
  );

  $data['commerce_order']['changed_day'] = array(
    'title' => t('Updated day'),
    'help' => t('In the form of DD (01 - 31).'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_day',
    ),
  );

  $data['commerce_order']['changed_week'] = array(
    'title' => t('Updated week'),
    'help' => t('In the form of WW (01 - 53).'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_week',
    ),
  );

  // Expose links to operate on the order.
  $data['commerce_order']['view_order'] = array(
    'field' => array(
      'title' => t('Link'),
      'help' => t('Provide a simple link to the administrator view of the order.'),
      'handler' => 'commerce_order_handler_field_order_link',
    ),
  );
  $data['commerce_order']['edit_order'] = array(
    'field' => array(
      'title' => t('Edit link'),
      'help' => t('Provide a simple link to edit the order.'),
      'handler' => 'commerce_order_handler_field_order_link_edit',
    ),
  );
  $data['commerce_order']['delete_order'] = array(
    'field' => array(
      'title' => t('Delete link'),
      'help' => t('Provide a simple link to delete the order.'),
      'handler' => 'commerce_order_handler_field_order_link_delete',
    ),
  );

  $data['commerce_order']['operations'] = array(
    'field' => array(
      'title' => t('Operations links'),
      'help' => t('Display all the available operations links for the order.'),
      'handler' => 'commerce_order_handler_field_order_operations',
    ),
  );

  $data['commerce_order']['empty_text'] = array(
    'title' => t('Empty text'),
    'help' => t('Displays an appropriate empty text message for order lists.'),
    'area' => array(
      'handler' => 'commerce_order_handler_area_empty_text',
    ),
  );

  // Define a handler for an area used to summarize a set of line items.
  $data['commerce_order']['order_total'] = array(
    'title' => t('Order total'),
    'help' => t('Displays the order total field formatted with its components list; requires an Order ID argument.'),
    'area' => array(
      'handler' => 'commerce_order_handler_area_order_total',
    ),
  );

  /**
   * Integrate the order revision table.
   */
  $data['commerce_order_revision']['table']['entity type'] = 'commerce_order';
  $data['commerce_order_revision']['table']['group']  = t('Commerce Order revision');

  // Advertise this table as a possible base table
  $data['commerce_order_revision']['table']['base'] = array(
    'field' => 'revision_id',
    'title' => t('Commerce Order revision'),
    'help' => t('Commerce Order revision is a history of changes to an order.'),
    'defaults' => array(
      'field' => 'order_number',
    ),
  );

  $data['commerce_order_revision']['table']['join'] = array(
    'commerce_order' => array(
      'left_field' => 'revision_id',
      'field' => 'revision_id',
    )
  );

  $data['commerce_order_revision']['table']['default_relationship'] = array(
    'commerce_order' => array(
      'table' => 'commerce_order',
      'field' => 'revision_id',
    ),
  );

  // Expose the revision order ID
  $data['commerce_order_revision']['order_id'] = array(
    'title' => t('Order ID', array(), array('context' => 'a drupal commerce order')),
    'help' => t('The unique internal identifier of the order.'),
    'field' => array(
      'handler' => 'commerce_order_handler_field_order',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'commerce_order_handler_argument_order_order_id',
      'numeric' => TRUE,
      'validate type' => 'order_id',
    ),
  );

  // Expose the revision order number
  $data['commerce_order_revision']['order_number'] = array(
    'title' => t('Order number', array(), array('context' => 'a drupal commerce order')),
    'help' => t('The customer facing number of the order.'),
    'field' => array(
      'handler' => 'commerce_order_handler_field_order',
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

  // Expose the revision ID.
  $data['commerce_order_revision']['revision_id'] = array(
    'title' => t('Revision ID'),
    'help' => t('The revision ID of the order revision.'),
    'field' => array(
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'commerce_order',
      'base field' => 'revision_id',
      'title' => t('Order'),
      'label' => t('Latest order revision'),
    ),
  );

  // Expose the order revision user ID.
  $data['commerce_order_revision']['revision_uid'] = array(
    'title' => t('User'),
    'help' => t('Relate an order revision to the user who created the revision.'),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'users',
      'base field' => 'uid',
      'field' => 'revision_uid',
      'field_name' => 'revision_uid',
      'label' => t('Revision user'),
    ),
  );


  // Expose the order revision contact email.
  $data['commerce_order_revision']['mail'] = array(
    'title' => t('E-mail'),
    'help' => t('The e-mail address used for correspondence about this order revision.'),
    'field' => array(
      'handler' => 'commerce_order_handler_field_order_mail',
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

  // Expose the order status for this revision.
  $data['commerce_order_revision']['status'] = array(
    'title' => t('Order status'),
    'help' => t('The workflow status of the order revision.'),
    'field' => array(
      'handler' => 'commerce_order_handler_field_order_status',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'commerce_order_handler_filter_order_status',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );

  // Expose the order revision log
  $data['commerce_order_revision']['log'] = array(
    'title' => t('Log message'),
    'help' => t('The log message entered when the revision was created.'),
    'field' => array(
      'handler' => 'views_handler_field_xss',
     ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
  );

  // Expose the revision timestamp
  $data['commerce_order_revision']['revision_timestamp'] = array(
    'title' => t('Revision date'),
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

/**
 * Implements hook_views_plugins
 */
function commerce_order_views_plugins() {
  return array(
    'argument validator' => array(
      'current_user_or_role' => array(
        'title' => t('Current user or role'),
        'handler' => 'commerce_order_plugin_argument_validate_user',
      ),
    ),
  );
}
