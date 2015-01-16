<?php

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
    'type' => 'integer',
    'label' => t('Order ID', array(), array('context' => 'a drupal commerce order')),
    'description' => t('The internal numeric ID of the order.'),
    'schema field' => 'order_id',
  );
  $properties['order_number'] = array(
    'type' => 'text',
    'label' => t('Order number', array(), array('context' => 'a drupal commerce order')),
    'description' => t('The order number displayed to the customer.'),
    'setter callback' => 'entity_property_verbatim_set',
    'schema field' => 'order_number',
  );
  $properties['status'] = array(
    'type' => 'text',
    'label' => t('Status'),
    'description' => t('The current status of the order.'),
    'setter callback' => 'entity_property_verbatim_set',
    'options list' => 'commerce_order_status_options_list',
    'required' => TRUE,
    'schema field' => 'status',
  );
  $properties['state'] = array(
    'type' => 'token',
    'label' => t('State'),
    'description' => t('The state of the order derived from its status.'),
    'getter callback' => 'commerce_order_get_properties',
    'options list' => 'commerce_order_state_options_list',
    'computed' => TRUE,
  );
  $properties['created'] = array(
    'type' => 'date',
    'label' => t('Date created'),
    'description' => t('The date the order was created.'),
    'setter callback' => 'entity_property_verbatim_set',
    'setter permission' => 'administer commerce_order entities',
    'schema field' => 'created',
  );
  $properties['changed'] = array(
    'type' => 'date',
    'label' => t('Date changed'),
    'description' => t('The date the order was most recently updated.'),
    'setter callback' => 'entity_property_verbatim_set',
    'setter permission' => 'administer commerce_order entities',
    'schema field' => 'changed',
  );
  $properties['hostname'] = array(
    'type' => 'text',
    'label' => t('Host name'),
    'description' => t('The IP address that created this order.'),
    'setter callback' => 'entity_property_verbatim_set',
    'setter permission' => 'administer commerce_order entities',
    'schema field' => 'hostname',
  );
  $properties['type'] = array(
    'type' => 'text',
    'label' => t('Type'),
    'description' => t('The human readable name of the order type.'),
    'setter callback' => 'entity_property_verbatim_set',
    'options list' => 'commerce_order_type_options_list',
    'required' => TRUE,
    'schema field' => 'type',
  );
  $properties['uid'] = array(
    'type' => 'integer',
    'label' => t("Owner ID"),
    'description' => t("The unique ID of the order owner."),
    'setter callback' => 'entity_property_verbatim_set',
    'setter permission' => 'administer commerce_order entities',
    'clear' => array('owner'),
    'schema field' => 'uid',
  );
  $properties['owner'] = array(
    'type' => 'user',
    'label' => t("Owner"),
    'description' => t("The owner of the order."),
    'getter callback' => 'commerce_order_get_properties',
    'setter callback' => 'commerce_order_set_properties',
    'setter permission' => 'administer commerce_order entities',
    'required' => TRUE,
    'computed' => TRUE,
    'clear' => array('uid'),
  );
  $properties['mail'] = array(
    'label' => t('Order e-mail'),
    'description' => t('The e-mail address associated with this order.'),
    'setter callback' => 'entity_property_verbatim_set',
    'validation callback' => 'valid_email_address',
    'schema field' => 'mail',
  );
  $properties['mail_username'] = array(
    'type' => 'text',
    'label' => t('Order e-mail prepared for username usage'),
    'description' => t('The e-mail address associated with this order with illegal characters for usernames replaced.'),
    'getter callback' => 'commerce_order_get_properties',
    'computed' => TRUE,
    'clear' => array('mail'),
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

  if (!empty($properties['commerce_line_items'])) {
    $info['commerce_order']['properties']['commerce_line_items'] = $properties['commerce_line_items'];
  }
  if (!empty($properties['commerce_order_total'])) {
    $info['commerce_order']['properties']['commerce_order_total'] = $properties['commerce_order_total'];
  }
}
