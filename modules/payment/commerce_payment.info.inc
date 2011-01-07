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

  // TODO: add remote_id, message, amount, currency_code, status, and remote_status.

  $properties['created'] = array(
    'label' => t('Date created'),
    'description' => t('The date the product was created.'),
    'type' => 'date',
    'setter callback' => 'entity_property_verbatim_set',
    'query callback' => 'entity_metadata_table_query',
    'setter permission' => 'administer customer profiles',
  );
  $properties['changed'] = array(
    'label' => t('Date updated'),
    'description' => t('The date the product was last updated.'),
    'type' => 'date',
    'setter callback' => 'entity_property_verbatim_set',
    'query callback' => 'entity_metadata_table_query',
    'setter permission' => 'administer customer profiles',
  );

  return $info;
}
