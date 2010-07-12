<?php
// $Id$

/**
 * Export Drupal Commerce Payment transactions to Views.
 */

/**
 * Implements hook_views_data()
 */
function commerce_payment_views_data() {
  $data = array();

  $data['commerce_payment_transaction']['table']['group']  = t('Commerce Payment Transaction');

  $data['commerce_payment_transaction']['table']['base'] = array(
    'field' => 'transaction_id',
    'title' => t('Commerce Payment Transaction'),
    'help' => t('The receipt of a payment transaction.'),
  );

  // Expose the transaction ID.
  $data['commerce_payment_transaction']['transaction_id'] = array(
    'title' => t('Transaction ID'),
    'help' => t('The unique internal identifier of the transaction.'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
  );

  // Expose the created timestamp.
  $data['commerce_payment_transaction']['created'] = array(
    'title' => t('Created date'),
    'help' => t('The date the transaction was created.'),
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

  // Expose the transaction payment method.
  $data['commerce_payment_transaction']['payment_method'] = array(
    'title' => t('Payment method'),
    'help' => t('The payment method of the transaction.'),
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

  // Expose the transaction remote ID.
  $data['commerce_payment_transaction']['remote_id'] = array(
    'title' => t('Remote identifier'),
    'help' => t('The remote identifier of this transaction.'),
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

  // Expose the transaction amount.
  $data['commerce_payment_transaction']['amount'] = array(
    'title' => t('Amount'),
    'help' => t('The amount of the transaction.'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
  );

  // Expose the transaction currency.
  $data['commerce_payment_transaction']['currency_code'] = array(
    'title' => t('Currency'),
    'help' => t('The currency of the transaction.'),
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

  // Expose the transaction message.
  $data['commerce_payment_transaction']['message'] = array(
    'title' => t('Message'),
    'help' => t('The message associated with the transaction.'),
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

  // Expose the transaction status.
  $data['commerce_payment_transaction']['status'] = array(
    'title' => t('Status'),
    'help' => t('The status of this transaction.'),
    'field' => array(
      'handler' => 'commerce_payment_handler_field_status',
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

  $data['commerce_payment_transaction']['total'] = array(
    'title' => t('Total'),
    'help' => t('Display the total.'),
    'area' => array(
      'handler' => 'commerce_payment_handler_area_total',
    ),
  );

  return $data;
}

/**
 * Implements hook_views_handlers().
 */
function commerce_payment_views_handlers() {
  return array(
    'info' => array(
      'path' => drupal_get_path('module', 'commerce_payment') . '/includes/views/handlers',
    ),
    'handlers' => array(
      // Field handlers
      'commerce_payment_handler_field_status' => array(
        'parent' => 'views_handler_field',
      ),
      // Footer handlers.
      'commerce_payment_handler_area_total' => array(
        'parent' => 'views_handler_area',
      ),
    ),
  );
}
