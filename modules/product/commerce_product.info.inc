<?php
// $Id$

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
  );
  $properties['sku'] = array(
    'label' => t('SKU'),
    'description' => t('The human readable product SKU.'),
    'type' => 'text',
    'setter callback' => 'entity_metadata_verbatim_set',
    'required' => TRUE,
    'query callback' => 'entity_metadata_table_query',
  );
  $properties['type'] = array(
    'label' => t('Type'),
    'description' => t('The human readable name of the product type.'),
    'type' => 'text',
    'options list' => 'commerce_product_type_get_name',
    'required' => TRUE,
    'query callback' => 'entity_metadata_table_query',
  );
  $properties['title'] = array(
    'label' => t('Title'),
    'description' => t('The title of the product.'),
    'type' => 'text',
    'setter callback' => 'entity_metadata_verbatim_set',
    'required' => TRUE,
    'query callback' => 'entity_metadata_table_query',
  );
  $properties['edit_url'] = array(
    'label' => t('Edit URL'),
    'description' => t("The URL of the product's edit page."),
    'getter callback' => 'commerce_product_get_properties',
    'type' => 'uri',
  );
  $properties['created'] = array(
    'label' => t('Date created'),
    'description' => t('The date the product was created.'),
    'type' => 'date',
    'setter callback' => 'entity_metadata_verbatim_set',
    'query callback' => 'entity_metadata_table_query',
    'setter permission' => 'administer products',
  );
  $properties['uid'] = array(
    'label' => t("Creator ID"),
    'type' => 'integer',
    'description' => t("The unique ID of the product creator."),
    'setter callback' => 'entity_metadata_verbatim_set',
    'setter permission' => 'administer products',
    'clear' => array('creator'),
    'query callback' => 'entity_metadata_table_query',
  );
  $properties['creator'] = array(
    'label' => t("Creator"),
    'type' => 'user',
    'description' => t("The creator of the product."),
    'getter callback' => 'commerce_product_get_properties',
    'setter callback' => 'commerce_product_set_properties',
    'setter permission' => 'administer products',
    'required' => TRUE,
    'clear' => array('uid'),
  );

  return $info;
}
