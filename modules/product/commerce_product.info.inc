<?php

/**
 * @file
 * Provides metadata for the product entity.
 */

/**
 * Implements hook_entity_property_info().
 */
function commerce_product_entity_property_info() {
  $info = array();

  // Add meta-data about the basic commerce_product properties.
  $properties = &$info['commerce_product']['properties'];

  $properties['product_id'] = array(
    'label' => t('Product ID'),
    'description' => t('The internal numeric ID of the product.'),
    'type' => 'integer',
    'schema field' => 'product_id',
  );
  $properties['sku'] = array(
    'label' => t('SKU'),
    'description' => t('The human readable product SKU.'),
    'type' => 'text',
    'setter callback' => 'entity_property_verbatim_set',
    'required' => TRUE,
    'schema field' => 'sku',
  );
  $properties['type'] = array(
    'label' => t('Type'),
    'description' => t('The type of the product.'),
    'type' => 'token',
    'setter callback' => 'entity_property_verbatim_set',
    'setter permission' => 'administer commerce_product entities',
    'options list' => 'commerce_product_type_options_list',
    'required' => TRUE,
    'schema field' => 'type',
  );
  $properties['title'] = array(
    'label' => t('Title'),
    'description' => t('The title of the product.'),
    'type' => 'text',
    'setter callback' => 'entity_property_verbatim_set',
    'required' => TRUE,
    'schema field' => 'title',
  );
  $properties['language'] = array(
    'label' => t('Language'),
    'type' => 'token',
    'description' => t('The language the product was created in.'),
    'setter callback' => 'entity_property_verbatim_set',
    'options list' => 'entity_metadata_language_list',
    'schema field' => 'language',
    'setter permission' => 'administer commerce_product entities',
  );
  $properties['status'] = array(
    'label' => t('Status'),
    'description' => t('Boolean indicating whether the product is active or disabled.'),
    'type' => 'boolean',
    'options list' => 'commerce_product_status_options_list',
    'setter callback' => 'entity_property_verbatim_set',
    'setter permission' => 'administer commerce_product entities',
    'schema field' => 'status',
  );
  $properties['created'] = array(
    'label' => t('Date created'),
    'description' => t('The date the product was created.'),
    'type' => 'date',
    'setter callback' => 'entity_property_verbatim_set',
    'setter permission' => 'administer commerce_product entities',
    'schema field' => 'created',
  );
  $properties['changed'] = array(
    'label' => t('Date updated'),
    'description' => t('The date the product was most recently updated.'),
    'type' => 'date',
    'setter callback' => 'entity_property_verbatim_set',
    'query callback' => 'entity_metadata_table_query',
    'setter permission' => 'administer commerce_product entities',
    'schema field' => 'changed',
  );
  $properties['uid'] = array(
    'label' => t('Creator ID'),
    'type' => 'integer',
    'description' => t('The unique ID of the product creator.'),
    'setter callback' => 'entity_property_verbatim_set',
    'setter permission' => 'administer commerce_product entities',
    'clear' => array('creator'),
    'schema field' => 'uid',
  );
  $properties['creator'] = array(
    'label' => t('Creator'),
    'type' => 'user',
    'description' => t('The creator of the product.'),
    'getter callback' => 'commerce_product_get_properties',
    'setter callback' => 'commerce_product_set_properties',
    'setter permission' => 'administer commerce_product entities',
    'required' => TRUE,
    'computed' => TRUE,
    'clear' => array('uid'),
  );

  return $info;
}

/**
 * Implements hook_entity_property_info_alter() on top of the Product module.
 */
function commerce_product_entity_property_info_alter(&$info) {
  if (!empty($info['commerce_product']['bundles'])) {
    // Move the default price property to the product by default; as it is a
    // required default field, this makes dealing with it more convenient.
    $properties = array();

    foreach ($info['commerce_product']['bundles'] as $bundle => $bundle_info) {
      $bundle_info += array('properties' => array());
      $properties += $bundle_info['properties'];
    }

    if (!empty($properties['commerce_price'])) {
      $info['commerce_product']['properties']['commerce_price'] = $properties['commerce_price'];
    }
  }
}
