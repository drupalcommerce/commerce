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
    'field' => 'order_number',
    'title' => t('Commerce Order'),
    'help' => t('Order placed in the store.'),
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

  // Define a handler for an area used to summarize a set of line items.
  $data['commerce_order']['order_total'] = array(
    'title' => t('Order total'),
    'help' => t('Displays the order total field formatted with its components list; requires an Order ID argument.'),
    'area' => array(
      'handler' => 'commerce_order_handler_area_order_total',
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
