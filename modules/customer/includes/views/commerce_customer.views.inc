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
    'access query tag' => 'commerce_customer_profile_access',
  );
  $data['commerce_customer_profile']['table']['entity type'] = 'commerce_customer_profile';

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

  $data['commerce_customer_profile']['created_fulldate'] = array(
    'title' => t('Created date'),
    'help' => t('In the form of CCYYMMDD.'),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_node_created_fulldate',
    ),
  );

  $data['commerce_customer_profile']['created_year_month'] = array(
    'title' => t('Created year + month'),
    'help' => t('In the form of YYYYMM.'),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_node_created_year_month',
    ),
  );

  $data['commerce_customer_profile']['created_timestamp_year'] = array(
    'title' => t('Created year'),
    'help' => t('In the form of YYYY.'),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_node_created_year',
    ),
  );

  $data['commerce_customer_profile']['created_month'] = array(
    'title' => t('Created month'),
    'help' => t('In the form of MM (01 - 12).'),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_node_created_month',
    ),
  );

  $data['commerce_customer_profile']['created_day'] = array(
    'title' => t('Created day'),
    'help' => t('In the form of DD (01 - 31).'),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_node_created_day',
    ),
  );

  $data['commerce_customer_profile']['created_week'] = array(
    'title' => t('Created week'),
    'help' => t('In the form of WW (01 - 53).'),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_node_created_week',
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

  $data['commerce_customer_profile']['changed_fulldate'] = array(
    'title' => t('Updated date'),
    'help' => t('In the form of CCYYMMDD.'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_fulldate',
    ),
  );

  $data['commerce_customer_profile']['changed_year_month'] = array(
    'title' => t('Updated year + month'),
    'help' => t('In the form of YYYYMM.'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_year_month',
    ),
  );

  $data['commerce_customer_profile']['changed_timestamp_year'] = array(
    'title' => t('Updated year'),
    'help' => t('In the form of YYYY.'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_year',
    ),
  );

  $data['commerce_customer_profile']['changed_month'] = array(
    'title' => t('Updated month'),
    'help' => t('In the form of MM (01 - 12).'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_month',
    ),
  );

  $data['commerce_customer_profile']['changed_day'] = array(
    'title' => t('Updated day'),
    'help' => t('In the form of DD (01 - 31).'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_day',
    ),
  );

  $data['commerce_customer_profile']['changed_week'] = array(
    'title' => t('Updated week'),
    'help' => t('In the form of WW (01 - 53).'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_week',
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

  $data['commerce_customer_profile']['empty_text'] = array(
    'title' => t('Empty text'),
    'help' => t('Displays an appropriate empty text message for customer profile lists.'),
    'area' => array(
      'handler' => 'commerce_customer_handler_area_empty_text',
    ),
  );

  return $data;
}

/**
 * Implements hook_field_views_data().
 */
function commerce_customer_field_views_data($field) {
  $data = field_views_field_default_views_data($field);

  // Build an array of bundles the customer profile reference field appears on.
  $bundles = array();

  foreach ($field['bundles'] as $entity => $entity_bundles) {
    $bundles[] = $entity . ' (' . implode(', ', $entity_bundles) . ')';
  }

  $replacements = array('!field_name' => $field['field_name'], '@bundles' => implode(', ', $bundles));

  foreach ($data as $table_name => $table_data) {
    foreach ($table_data as $field_name => $field_data) {
      if (isset($field_data['filter']['field_name']) && $field_name != 'delta') {
        $data[$table_name][$field_name]['relationship'] = array(
          'title' => t('Referenced customer profile'),
          'label' => t('Customer profile referenced by !field_name', $replacements),
          'help' => t('Relate this entity to the customer profile referenced by its !field_name value.', $replacements) . '<br />' . t('Appears in: @bundles.', $replacements),
          'base' => 'commerce_customer_profile',
          'base field' => 'profile_id',
          'handler' => 'views_handler_relationship',
        );
      }
    }
  }

  return $data;
}
