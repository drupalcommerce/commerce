<?php
// $Id$

/**
 * @file
 * Provides metadata for the order entity.
 */

/**
 * Implements hook_entity_property_info().
 */
function commerce_order_entity_property_info() {
  $info = array();

  // Add meta-data about the basic commerce_order properties.
  $properties = &$info['commerce_order']['properties'];

  $properties['order_id'] = array(
    'label' => t('Order ID', array(), array('context' => 'a drupal commerce order')),
    'description' => t('The internal numeric ID of the order.'),
    'type' => 'integer',
  );
  $properties['order_number'] = array(
    'label' => t('Order number', array(), array('context' => 'a drupal commerce order')),
    'description' => t('The order number displayed to the customer.'),
    'type' => 'text',
    'setter callback' => 'entity_property_verbatim_set',
    'required' => TRUE,
    'query callback' => 'entity_metadata_table_query',
  );
  $properties['view_url'] = array(
    'label' => t('View URL'),
    'description' => t('The URL a customer can visit to view the order.'),
    'getter callback' => 'commerce_order_get_properties',
    'type' => 'uri',
  );
  $properties['admin_url'] = array(
    'label' => t('Admin URL'),
    'description' => t("The URL of the order's administrative view page."),
    'getter callback' => 'commerce_order_get_properties',
    'type' => 'uri',
  );
  $properties['edit_url'] = array(
    'label' => t('Edit URL'),
    'description' => t("The URL of the order's edit page."),
    'getter callback' => 'commerce_order_get_properties',
    'type' => 'uri',
  );
  $properties['status'] = array(
    'label' => t('Status'),
    'description' => t('The current status of the order.'),
    'type' => 'text',
    'setter callback' => 'entity_property_verbatim_set',
    'options list' => 'commerce_order_status_options_list',
    'query callback' => 'entity_metadata_table_query',
    'required' => TRUE,
  );
  $properties['state'] = array(
    'label' => t('State'),
    'description' => t('The state of the order derived from its status.'),
    'type' => 'token',
    'getter callback' => 'commerce_order_get_properties',
    'options list' => 'commerce_order_state_options_list',
  );
  $properties['created'] = array(
    'label' => t('Date created'),
    'description' => t('The date the order was created.'),
    'type' => 'date',
    'setter callback' => 'entity_property_verbatim_set',
    'query callback' => 'entity_metadata_table_query',
    'setter permission' => 'administer orders',
  );
  $properties['changed'] = array(
    'label' => t('Date changed'),
    'description' => t('The date the order was most recently updated.'),
    'type' => 'date',
    'setter callback' => 'entity_property_verbatim_set',
    'query callback' => 'entity_metadata_table_query',
    'setter permission' => 'administer orders',
  );
  $properties['type'] = array(
    'label' => t('Type'),
    'description' => t('The human readable name of the order type.'),
    'type' => 'text',
    'options list' => 'commerce_order_type_options_list',
    'required' => TRUE,
    'query callback' => 'entity_metadata_table_query',
  );
  $properties['uid'] = array(
    'label' => t("Owner ID"),
    'type' => 'integer',
    'description' => t("The unique ID of the order owner."),
    'setter callback' => 'entity_property_verbatim_set',
    'setter permission' => 'administer orders',
    'clear' => array('owner'),
    'query callback' => 'entity_metadata_table_query',
  );
  $properties['owner'] = array(
    'label' => t("Owner"),
    'type' => 'user',
    'description' => t("The owner of the order."),
    'getter callback' => 'commerce_order_get_properties',
    'setter callback' => 'commerce_order_set_properties',
    'setter permission' => 'administer orders',
    'required' => TRUE,
    'clear' => array('uid'),
  );
  $properties['mail'] = array(
    'label' => t('Order e-mail'),
    'description' => t('The e-mail address associated with this order.'),
    'setter callback' => 'entity_property_verbatim_set',
    'validation callback' => 'valid_email_address',
    'query callback' => 'entity_metadata_table_query',
  );

  return $info;
}

/**
 * Implements hook_entity_property_info_alter() on top of the Order module.
 */
function commerce_order_entity_property_info_alter(&$info) {
  // Move the line items and order total properties to the order by default; as
  // they are required default fields, this makes dealing with them more convenient.
  $properties = array();

  foreach ($info['commerce_order']['bundles'] as $bundle => $bundle_info) {
    $bundle_info += array('properties' => array());
    $properties += $bundle_info['properties'];
  }

  $info['commerce_order']['properties']['commerce_line_items'] = $properties['commerce_line_items'];
  $info['commerce_order']['properties']['commerce_order_total'] = $properties['commerce_order_total'];
}
