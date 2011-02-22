<?php
// $Id$

/**
 * @file
 * Provides metadata for the line item entity.
 */

/**
 * Implements hook_entity_property_info().
 */
function commerce_line_item_entity_property_info() {
  $info = array();

  // Add meta-data about the basic commerce_line_item properties.
  $properties = &$info['commerce_line_item']['properties'];

  $properties['line_item_id'] = array(
    'label' => t('Line item ID'),
    'description' => t('The internal numeric ID of the line item.'),
    'type' => 'integer',
  );
  $properties['order_id'] = array(
    'label' => t('Order ID', array(), array('context' => 'a drupal commerce order')),
    'type' => 'integer',
    'description' => t('The unique ID of the order the line item belongs to.'),
    'setter callback' => 'entity_property_verbatim_set',
    'setter permission' => 'administer line items',
    'clear' => array('order'),
    'query callback' => 'entity_metadata_table_query',
  );
  $properties['order'] = array(
    'label' => t('Order', array(), array('context' => 'a drupal commerce order')),
    'type' => 'commerce_order',
    'description' => t('The order the line item belongs to.'),
    'getter callback' => 'commerce_line_item_get_properties',
    'setter callback' => 'commerce_line_item_set_properties',
    'setter permission' => 'administer line items',
    'required' => TRUE,
    'clear' => array('order_id'),
  );
  $properties['type'] = array(
    'label' => t('Type'),
    'description' => t('The human readable name of the line item type.'),
    'type' => 'token',
    'setter callback' => 'entity_property_verbatim_set',
    'options list' => 'commerce_line_item_type_options_list',
    'query callback' => 'entity_metadata_table_query',
    'required' => TRUE,
  );
  $properties['line_item_label'] = array(
    'label' => t('Line item label'),
    'description' => t('The label displayed with the line item.'),
    'type' => 'text',
    'setter callback' => 'entity_property_verbatim_set',
    'query callback' => 'entity_metadata_table_query',
    'required' => TRUE,
  );
  $properties['quantity'] = array(
    'label' => t('Quantity'),
    'description' => t('Quantity associated with this line item'),
    'type' => 'decimal',
    'getter callback' => 'entity_property_verbatim_get',
    'setter callback' => 'entity_property_verbatim_set',
    'required' => TRUE,
  );
  $properties['created'] = array(
    'label' => t('Date created'),
    'description' => t('The date the line item was created.'),
    'type' => 'date',
    'setter callback' => 'entity_metadata_verbatim_set',
    'setter permission' => 'administer line items',
    'query callback' => 'entity_metadata_table_query',
  );
  $properties['changed'] = array(
    'label' => t('Date changed'),
    'description' => t('The date the line item was most recently updated.'),
    'type' => 'date',
    'query callback' => 'entity_metadata_table_query',
  );

  return $info;
}

/**
 * Implements hook_entity_property_info_alter() on top of the Line Item module.
 */
function commerce_line_item_entity_property_info_alter(&$info) {
  if (!empty($info['commerce_line_item']['bundles'])) {
    // Move the price properties to the line item by default; as they are required
    // default fields, this makes dealing with them more convenient.
    $properties = array();

    foreach ($info['commerce_line_item']['bundles'] as $bundle => $bundle_info) {
      $bundle_info += array('properties' => array());
      $properties += $bundle_info['properties'];
    }

    $info['commerce_line_item']['properties']['commerce_unit_price'] = $properties['commerce_unit_price'];
    $info['commerce_line_item']['properties']['commerce_total'] = $properties['commerce_total'];
  }
}
