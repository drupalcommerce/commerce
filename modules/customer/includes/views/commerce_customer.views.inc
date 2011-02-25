<?php

/**
 * Export Drupal Commerce customer profiles to Views.
 */

/**
 * Implements hook_views_data()
 */
function commerce_customer_views_data() {
  $data = array();

  $data['commerce_customer_profile']['table']['group']  = t('Commerce Customer Profile');

  $data['commerce_customer_profile']['table']['base'] = array(
    'field' => 'profile_id',
    'title' => t('Commerce Customer Profile'),
    'help' => t('Customer profiles containing addresses and other customer information.'),
  );

  // Expose the profile ID.
  $data['commerce_customer_profile']['profile_id'] = array(
    'title' => t('Profile ID'),
    'help' => t('The unique internal identifier of the profile.'),
    'field' => array(
      'handler' => 'commerce_customer_handler_field_customer_profile',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
  );

  // Expose the profile type.
  $data['commerce_customer_profile']['type'] = array(
    'title' => t('Type'),
    'help' => t('The human-readable name of the type of the customer profile.'),
    'field' => array(
      'handler' => 'commerce_customer_handler_field_customer_profile_type',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'commerce_customer_handler_filter_customer_profile_type',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );

  // Expose the owner uid.
  $data['commerce_customer_profile']['uid'] = array(
    'title' => t('Owner'),
    'help' => t('Relate a profile to the user it belongs to.'),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'users',
      'field' => 'uid',
      'label' => t('Profile owner'),
    ),
  );

  // Expose the profile status.
  $data['commerce_customer_profile']['status'] = array(
    'title' => t('Status'),
    'help' => t('Whether or not the profile is active.'),
    'field' => array(
      'handler' => 'views_handler_field_boolean',
      'click sortable' => TRUE,
      'output formats' => array(
        'active-disabled' => array(t('Active'), t('Disabled')),
      ),
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
      'label' => t('Active'),
      'type' => 'yes-no',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // Expose the created and changed timestamps.
  $data['commerce_customer_profile']['created'] = array(
    'title' => t('Created date'),
    'help' => t('The date the profile was created.'),
    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
  );

  $data['commerce_customer_profile']['changed'] = array(
    'title' => t('Updated date'),
    'help' => t('The date the profile was last updated.'),
    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
  );

  // Expose links to operate on the profile.
  $data['commerce_customer_profile']['view_customer_profile'] = array(
    'field' => array(
      'title' => t('Link'),
      'help' => t('Provide a simple link to the administrator view of the profile.'),
      'handler' => 'commerce_customer_handler_field_customer_profile_link',
    ),
  );
  $data['commerce_customer_profile']['edit_customer_profile'] = array(
    'field' => array(
      'title' => t('Edit link'),
      'help' => t('Provide a simple link to edit the profile.'),
      'handler' => 'commerce_customer_handler_field_customer_profile_link_edit',
    ),
  );
  $data['commerce_customer_profile']['delete_customer_profile'] = array(
    'field' => array(
      'title' => t('Delete link'),
      'help' => t('Provide a simple link to delete the profile.'),
      'handler' => 'commerce_customer_handler_field_customer_profile_link_delete',
    ),
  );

  return $data;
}
