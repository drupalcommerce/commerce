<?php
// $Id$

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
    'title' => t('Order ID'),
    'help' => t('The unique internal identifier of the order.'),
    'field' => array(
      'handler' => 'views_handler_field_commerce_order',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    // TODO: Provide an argument handler for orders.
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
  );

  // Expose the order number.
  $data['commerce_order']['order_number'] = array(
    'title' => t('Order number'),
    'help' => t('The unique customer facing number of the order.'),
    'field' => array(
      'handler' => 'views_handler_field_commerce_order',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    // TODO: Provide an argument handler for orders.
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
  );

  // Expose the creator uid.
  $data['commerce_order']['creator_uid'] = array(
    'title' => t('Creator'),
    'help' => t('Relate an order to the user who created it.'),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'users',
      'field' => 'uid',
      'label' => t('Order creator'),
    ),
  );

  // Expose the customer uid.
  $data['commerce_order']['customer_uid'] = array(
    'title' => t('Customer'),
    'help' => t("Relate an order to the customer's user account."),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'users',
      'field' => 'uid',
      'label' => t('Order customer'),
    ),
  );

  // Expose the order status.
  $data['commerce_order']['status'] = array(
    'title' => t('Order status'),
    'help' => t('The workflow status of the order.'),
    'field' => array(
      'handler' => 'views_handler_field_commerce_order_status',
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
      'handler' => 'views_handler_field_commerce_order_link',
    ),
  );
  $data['commerce_order']['edit_order'] = array(
    'field' => array(
      'title' => t('Edit link'),
      'help' => t('Provide a simple link to edit the order.'),
      'handler' => 'views_handler_field_commerce_order_link_edit',
    ),
  );
  $data['commerce_order']['delete_order'] = array(
    'field' => array(
      'title' => t('Delete link'),
      'help' => t('Provide a simple link to delete the order.'),
      'handler' => 'views_handler_field_commerce_order_link_delete',
    ),
  );

  return $data;
}

/**
 * Implements hook_views_handlers().
 *
 * TODO: Determine if this hook is still even necessary given class autoloading.
 */
function commerce_order_views_handlers() {
  return array(
    'info' => array(
      'path' => drupal_get_path('module', 'commerce_order') . '/includes/views/handlers',
    ),
    'handlers' => array(
      // Field handlers
      'views_handler_field_commerce_order' => array(
        'parent' => 'views_handler_field',
      ),
      'views_handler_field_commerce_order_link' => array(
        'parent' => 'views_handler_field',
      ),
      'views_handler_field_commerce_order_link_edit' => array(
        'parent' => 'views_handler_field_commerce_order_link',
      ),
      'views_handler_field_commerce_order_link_delete' => array(
        'parent' => 'views_handler_field_commerce_order_link',
      ),
      'views_handler_field_commerce_order_status' => array(
        'parent' => 'views_handler_field_commerce_order',
      ),
    ),
  );
}
