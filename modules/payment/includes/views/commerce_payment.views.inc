<?php

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
    'access query tag' => 'commerce_payment_transaction_access',
  );
  $data['commerce_payment_transaction']['table']['entity type'] = 'commerce_payment_transaction';

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

  // Expose the order ID.
  $data['commerce_payment_transaction']['order_id'] = array(
    'title' => t('Order ID', array(), array('context' => 'a drupal commerce order')),
    'help' => t('The unique internal identifier of the associated order.'),
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
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'commerce_order',
      'field' => 'order_id',
      'label' => t('Order', array(), array('context' => 'a drupal commerce order')),
    ),
  );

  // Expose the transaction payment method.
  $data['commerce_payment_transaction']['payment_method'] = array(
    'title' => t('Payment method'),
    'help' => t('The payment method of the transaction.'),
    'field' => array(
      'handler' => 'commerce_payment_handler_field_payment_method',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'commerce_payment_handler_filter_payment_method',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );

  // Expose the transaction's remote ID.
  $data['commerce_payment_transaction']['remote_id'] = array(
    'title' => t('Remote ID'),
    'help' => t('The remote identifier of this transaction at the payment provider.'),
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
      'handler' => 'commerce_payment_handler_field_amount',
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
      'handler' => 'commerce_payment_handler_field_currency_code',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'commerce_payment_handler_filter_currency_code',
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
      'handler' => 'commerce_payment_handler_field_message',
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
      'handler' => 'commerce_payment_handler_filter_payment_transaction_status',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );

  // Expose the transaction's remote status.
  $data['commerce_payment_transaction']['remote_status'] = array(
    'title' => t('Remote status'),
    'help' => t('The status of this transaction at the payment provider.'),
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

  // Expose the created and changed timestamps.
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

  $data['commerce_payment_transaction']['created_fulldate'] = array(
    'title' => t('Created date'),
    'help' => t('In the form of CCYYMMDD.'),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_node_created_fulldate',
    ),
  );

  $data['commerce_payment_transaction']['created_year_month'] = array(
    'title' => t('Created year + month'),
    'help' => t('In the form of YYYYMM.'),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_node_created_year_month',
    ),
  );

  $data['commerce_payment_transaction']['created_timestamp_year'] = array(
    'title' => t('Created year'),
    'help' => t('In the form of YYYY.'),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_node_created_year',
    ),
  );

  $data['commerce_payment_transaction']['created_month'] = array(
    'title' => t('Created month'),
    'help' => t('In the form of MM (01 - 12).'),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_node_created_month',
    ),
  );

  $data['commerce_payment_transaction']['created_day'] = array(
    'title' => t('Created day'),
    'help' => t('In the form of DD (01 - 31).'),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_node_created_day',
    ),
  );

  $data['commerce_payment_transaction']['created_week'] = array(
    'title' => t('Created week'),
    'help' => t('In the form of WW (01 - 53).'),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_node_created_week',
    ),
  );

  $data['commerce_payment_transaction']['changed'] = array(
    'title' => t('Changed date'),
    'help' => t('The date the transaction was last changed.'),
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

  $data['commerce_payment_transaction']['changed_fulldate'] = array(
    'title' => t('Updated date'),
    'help' => t('In the form of CCYYMMDD.'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_fulldate',
    ),
  );

  $data['commerce_payment_transaction']['changed_year_month'] = array(
    'title' => t('Updated year + month'),
    'help' => t('In the form of YYYYMM.'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_year_month',
    ),
  );

  $data['commerce_payment_transaction']['changed_timestamp_year'] = array(
    'title' => t('Updated year'),
    'help' => t('In the form of YYYY.'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_year',
    ),
  );

  $data['commerce_payment_transaction']['changed_month'] = array(
    'title' => t('Updated month'),
    'help' => t('In the form of MM (01 - 12).'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_month',
    ),
  );

  $data['commerce_payment_transaction']['changed_day'] = array(
    'title' => t('Updated day'),
    'help' => t('In the form of DD (01 - 31).'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_day',
    ),
  );

  $data['commerce_payment_transaction']['changed_week'] = array(
    'title' => t('Updated week'),
    'help' => t('In the form of WW (01 - 53).'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_week',
    ),
  );

  $data['commerce_payment_transaction']['delete_transaction'] = array(
    'field' => array(
      'title' => t('Delete link'),
      'help' => t('Provide a simple link to delete the payment transaction.'),
      'handler' => 'commerce_payment_handler_field_payment_transaction_link_delete',
    ),
  );

  $data['commerce_payment_transaction']['operations'] = array(
    'field' => array(
      'title' => t('Operations links'),
      'help' => t('Display all the available operations links for the transaction.'),
      'handler' => 'commerce_payment_handler_field_payment_transaction_operations',
    ),
  );

  $data['commerce_payment_transaction']['totals'] = array(
    'title' => t('Totals'),
    'help' => t('Display transaction total and order balance information for all transactions in the View.'),
    'area' => array(
      'handler' => 'commerce_payment_handler_area_totals',
    ),
  );

  return $data;
}


/**
 * Implements hook_views_data_alter()
 */
function commerce_payment_views_data_alter(&$data) {
  $data['commerce_order']['balance']['field'] = array(
    'title' => t('Order Balance'),
    'help' => t('Total transaction payment balance for the order.'),
    'handler' => 'commerce_payment_handler_field_balance',
  );

  // Define the relationship from commerce_order to commerce_payment_transaction.
  $data['commerce_order']['table']['join']['commerce_payment_transaction'] = array(
    'left_field' => 'order_id',
    'field' => 'order_id',
  );

  $data['commerce_order']['payment_transaction']['relationship'] = array(
    'title' => t('Payment Transaction'),
    'help' => t("Relate this order to its payment transactions. This relationship will cause duplicated records if there are multiple transactions per order."),
    'handler' => 'views_handler_relationship',
    'base' => 'commerce_payment_transaction',
    'base field' => 'order_id',
    'field' => 'order_id',
    'label' => t('Transaction', array(), array('context' => 'a drupal commerce transaction')),
  );

  $data['commerce_order']['payment_transaction_representative']['relationship'] = array(
    'title' => t('Representative payment transaction'),
    'label'  => t('Representative payment transaction'),
    'help' => t('Obtains a single representative payment transaction for each order, according to a chosen sort criterion.'),
    'handler' => 'views_handler_relationship_groupwise_max',
    'relationship field' => 'order_id',
    'outer field' => 'commerce_order.order_id',
    'argument table' => 'commerce_order',
    'argument field' =>  'order_id',
    'base'   => 'commerce_payment_transaction',
    'field'  => 'transaction_id',
  );

}
