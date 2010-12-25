<?php
// $Id$

/**
 * @file
 * Provides metadata for the customer profile entity.
 */

/**
 * Implements hook_entity_property_info().
 */
function commerce_customer_entity_property_info() {
  $info = array();

  // Add meta-data about the basic commerce_customer_profile properties.
  $properties = &$info['commerce_customer_profile']['properties'];

  $properties['profile_id'] = array(
    'label' => t('Profile ID'),
    'description' => t('The internal numeric ID of the profile.'),
    'type' => 'integer',
  );
  $properties['type'] = array(
    'label' => t('Type'),
    'description' => t('The type of the profile.'),
    'type' => 'token',
    'setter callback' => 'entity_property_verbatim_set',
    'setter permission' => 'administer customer profiles',
    'options list' => 'commerce_customer_profile_type_options_list',
    'required' => TRUE,
    'query callback' => 'entity_metadata_table_query',
  );
  $properties['uid'] = array(
    'label' => t('User ID'),
    'type' => 'integer',
    'description' => t("The unique ID of the user the customer profile belongs to."),
    'setter callback' => 'entity_property_verbatim_set',
    'setter permission' => 'administer customer profiles',
    'clear' => array('user'),
    'query callback' => 'entity_metadata_table_query',
  );
  $properties['user'] = array(
    'label' => t('User'),
    'type' => 'user',
    'description' => t("The user the customer profile belongs to."),
    'getter callback' => 'commerce_customer_profile_get_properties',
    'setter callback' => 'commerce_customer_profile_set_properties',
    'setter permission' => 'administer customer profiles',
    'required' => TRUE,
    'clear' => array('uid'),
  );
  $properties['status'] = array(
    'label' => t('Status'),
    'description' => t('Whether the profile is active.'),
    'setter callback' => 'entity_property_verbatim_set',
    'setter permission' => 'administer customer profiles',
    'query callback' => 'entity_metadata_table_query',
    'type' => 'boolean',
  );
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
