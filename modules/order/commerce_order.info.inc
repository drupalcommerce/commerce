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
    'label' => t('Order ID'),
    'description' => t('The internal numeric ID of the order.'),
    'type' => 'integer',
  );
  $properties['order_number'] = array(
    'label' => t('Order number'),
    'description' => t('The order number displayed to the customer.'),
    'type' => 'text',
    'setter callback' => 'entity_metadata_verbatim_set',
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
  $properties['created'] = array(
    'label' => t('Date created'),
    'description' => t('The date the order was created.'),
    'type' => 'date',
    'setter callback' => 'entity_metadata_verbatim_set',
    'query callback' => 'entity_metadata_table_query',
    'setter permission' => 'administer orders',
  );
  $properties['type'] = array(
    'label' => t('Type'),
    'description' => t('The human readable name of the order type.'),
    'type' => 'text',
    'options list' => 'commerce_order_type_get_name',
    'required' => TRUE,
    'query callback' => 'entity_metadata_table_query',
  );
  $properties['uid'] = array(
    'label' => t("Owner ID"),
    'type' => 'integer',
    'description' => t("The unique ID of the order owner."),
    'setter callback' => 'entity_metadata_verbatim_set',
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
  $properties['line_items'] = array(
    'label' => t('Line items'),
    'description' => t('The line items attached to this order.'),
    'type' => 'list<commerce_line_item>',
    'getter callback' => 'commerce_order_get_properties',
  );

  return $info;
}
