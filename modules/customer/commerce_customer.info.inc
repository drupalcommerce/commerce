<?php

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
    'description' => t('The internal numeric ID of the customer profile.'),
    'type' => 'integer',
    'schema field' => 'profile_id',
  );
  $properties['type'] = array(
    'label' => t('Type'),
    'description' => t('The type of the customer profile.'),
    'type' => 'token',
    'setter callback' => 'entity_property_verbatim_set',
    'setter permission' => 'administer commerce_customer_profile entities',
    'options list' => 'commerce_customer_profile_type_options_list',
    'required' => TRUE,
    'schema field' => 'type',
  );
  $properties['uid'] = array(
    'label' => t('User ID'),
    'type' => 'integer',
    'description' => t("The unique ID of the user the customer profile belongs to."),
    'setter callback' => 'entity_property_verbatim_set',
    'setter permission' => 'administer commerce_customer_profile entities',
    'clear' => array('user'),
    'schema field' => 'uid',
  );
  $properties['user'] = array(
    'label' => t('User'),
    'type' => 'user',
    'description' => t("The user the customer profile belongs to."),
    'getter callback' => 'commerce_customer_profile_get_properties',
    'setter callback' => 'commerce_customer_profile_set_properties',
    'setter permission' => 'administer commerce_customer_profile entities',
    'required' => TRUE,
    'computed' => TRUE,
    'clear' => array('uid'),
  );
  $properties['status'] = array(
    'label' => t('Status'),
    'description' => t('Whether or not the customer profile is active.'),
    'setter callback' => 'entity_property_verbatim_set',
    'setter permission' => 'administer commerce_customer_profile entities',
    'type' => 'boolean',
    'schema field' => 'status',
  );
  $properties['created'] = array(
    'label' => t('Date created'),
    'description' => t('The date the customer profile was created.'),
    'type' => 'date',
    'setter callback' => 'entity_property_verbatim_set',
    'setter permission' => 'administer commerce_customer_profile entities',
    'schema field' => 'created',
  );
  $properties['changed'] = array(
    'label' => t('Date updated'),
    'description' => t('The date the customer profile was last updated.'),
    'type' => 'date',
    'setter callback' => 'entity_property_verbatim_set',
    'setter permission' => 'administer commerce_customer_profile entities',
    'schema field' => 'changed',
  );

  return $info;
}
