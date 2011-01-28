<?php
// $Id$

/**
 * @file
 * Provides metadata for the payment transaction entity.
 */

/**
 * Implements hook_entity_property_info().
 */
function commerce_payment_entity_property_info() {
  $info = array();

  // Add meta-data about the basic commerce_payment_transaction properties.
  $properties = &$info['commerce_payment_transaction']['properties'];

  $properties['transaction_id'] = array(
    'label' => t('Transaction ID'),
    'description' => t('The internal numeric ID of the transaction.'),
    'type' => 'integer',
  );
  $properties['uid'] = array(
    'label' => t('User ID'),
    'type' => 'integer',
    'description' => t("The unique ID of the user who created the transaction."),
    'setter callback' => 'entity_property_verbatim_set',
    'setter permission' => 'administer payment transactions',
    'clear' => array('user'),
    'query callback' => 'entity_metadata_table_query',
  );
  $properties['user'] = array(
    'label' => t('User'),
    'type' => 'user',
    'description' => t("The user who created the transaction."),
    'getter callback' => 'commerce_payment_transaction_get_properties',
    'setter callback' => 'commerce_payment_transaction_set_properties',
    'setter permission' => 'administer payment transactions',
    'required' => TRUE,
    'clear' => array('uid'),
  );
  $properties['order_id'] = array(
    'label' => t('Order ID', array(), array('context' => 'a drupal commerce order')),
    'type' => 'integer',
    'description' => t("The unique ID of the order the transaction belongs to."),
    'setter callback' => 'entity_property_verbatim_set',
    'setter permission' => 'administer payment transactions',
    'clear' => array('order'),
    'query callback' => 'entity_metadata_table_query',
  );
  $properties['order'] = array(
    'label' => t('Order', array(), array('context' => 'a drupal commerce order')),
    'type' => 'commerce_order',
    'description' => t("The order the transaction belongs to."),
    'getter callback' => 'commerce_payment_transaction_get_properties',
    'setter callback' => 'commerce_payment_transaction_set_properties',
    'setter permission' => 'administer payment transactions',
    'required' => TRUE,
    'clear' => array('order_id'),
  );
  $properties['payment_method'] = array(
    'label' => t('Payment method'),
    'description' => t('The payment method of the transaction.'),
    'type' => 'token',
    'setter callback' => 'entity_property_verbatim_set',
    'setter permission' => 'administer payment transactions',
    'options list' => 'commerce_payment_method_options_list',
    'required' => TRUE,
    'query callback' => 'entity_metadata_table_query',
  );
  $properties['created'] = array(
    'label' => t('Date created'),
    'description' => t('The date the payment was created.'),
    'type' => 'date',
    'setter callback' => 'entity_property_verbatim_set',
    'query callback' => 'entity_metadata_table_query',
    'setter permission' => 'administer payment transactions',
  );
  $properties['changed'] = array(
    'label' => t('Date updated'),
    'description' => t('The date the payment was last updated.'),
    'type' => 'date',
    'setter callback' => 'entity_property_verbatim_set',
    'query callback' => 'entity_metadata_table_query',
    'setter permission' => 'administer payment transactions',
  );
  $properties['remote_id'] = array(
    'label' => t('Remote id'),
    'description' => t('The remote identifier for this transaction.'),
    'type' => 'text',
    'query callback' => 'entity_metadata_table_query',
  );
  $properties['amount'] = array(
    'label' => t('Amount'),
    'description' => t('The amount for this transaction.'),
    'type' => 'decimal',
    'query callback' => 'entity_metadata_table_query',
  );
  $properties['currency_code'] = array(
    'label' => t('Currency Code'),
    'description' => t('The currency code for this transaction.'),
    'type' => 'text',
    'query callback' => 'entity_metadata_table_query',
  );
  $properties['message'] = array(
    'label' => t('Message'),
    'description' => t('Message for this transaction.'),
    'type' => 'text',
    'getter callback' => 'commerce_payment_transaction_get_properties',
  );
  $properties['status'] = array(
    'label' => t('Status'),
    'description' => t('Status of the payment transaction.'),
    'type' => 'text',
    'setter callback' => 'entity_property_verbatim_set',
    'query callback' => 'entity_metadata_table_query',
    'setter permission' => 'administer payment transactions',
  );
  $properties['remote_status'] = array(
    'label' => t('Remote status'),
    'description' => t('Remote status of the payment transaction.'),
    'type' => 'text',
    'setter callback' => 'entity_property_verbatim_set',
    'query callback' => 'entity_metadata_table_query',
    'setter permission' => 'administer payment transactions',
  );

  return $info;
}
