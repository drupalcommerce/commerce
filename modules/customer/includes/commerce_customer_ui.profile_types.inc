<?php
// $Id$

/**
 * @file
 */


/**
 * Menu callback: display an overview of available types.
 */
function commerce_customer_ui_customer_profile_types_overview() {
  drupal_add_css(drupal_get_path('module', 'commerce_customer_ui') . '/theme/commerce_customer_ui.profile_types.css');

  $header = array(
    t('Name'),
    t('Operations'),
  );

  $rows = array();

  // Loop through all defined customer profile types.
  foreach (commerce_customer_profile_types() as $type => $profile_type) {
    // Build the operation links for the current profile type.
    $links = menu_contextual_links('commerce-customer-profile-type', 'admin/commerce/customer-profiles/types', array(strtr($type, array('_' => '-'))));

    // Add the profile type's row to the table's rows array.
    $rows[] = array(
      theme('customer_profile_type_admin_overview', array('profile_type' => $profile_type)),
      theme('links', array('links' => $links, 'attributes' => array('class' => 'links inline operations'))),
    );
  }

  // If no profile types are defined...
  if (empty($rows)) {
    // Add a standard empty row with a link to add a new profile type.
    $rows[] = array(
      array(
        'data' => t('There are no customer profile types yet. <a href="@link">Add profile type</a>.', array('@link' => url('admin/commerce/customers/profiles/types/add'))),
        'colspan' => 2,
      )
    );
  }

  return theme('table', array('header' => $header, 'rows' => $rows));
}

/**
 * Builds an overview of a customer profile type for display to an administrator.
 *
 * @param $variables
 *   An array of variables used to generate the display; by default includes the
 *     type key with a value of the profile type object.
 *
 * @ingroup themeable
 */
function theme_customer_profile_type_admin_overview($variables) {
  $profile_type = $variables['profile_type'];

  $output = check_plain($profile_type->name);
  $output .= ' <small> (Machine name: ' . check_plain($profile_type->type) . ')</small>';
  $output .= '<div class="description">' . filter_xss_admin($profile_type->description) . '</div>';

  return $output;
}

/**
 * Form callback wrapper: create or edit a customer profile type.
 *
 * @param $profile_type
 *   The customer profile type object being edited by this form.
 *
 * @see commerce_customer_customer_profile_type_form()
 */
function commerce_customer_ui_customer_profile_type_form_wrapper($profile_type) {
  // Add the breadcrumb for the form's location.
  commerce_customer_ui_set_breadcrumb(TRUE);

  // Include the forms file from the Customer module.
  module_load_include('inc', 'commerce_customer', 'includes/commerce_customer_profile.forms');

  return drupal_get_form('commerce_customer_ui_customer_profile_type_form', $profile_type);
}

/**
 * Form callback wrapper: confirmation form for deleting a customer profile type.
 *
 * @param $profile_type
 *   The profile type object being deleted by this form.
 *
 * @see commerce_customer_customer_profile_type_delete_form()
 */
function commerce_customer_ui_customer_profile_type_delete_form_wrapper($profile_type) {
  // Add the breadcrumb for the form's location.
  commerce_profile_ui_set_breadcrumb(TRUE, TRUE);

  // Include the forms file from the Customer module.
  module_load_include('inc', 'commerce_customer', 'includes/commerce_customer_profile.forms');

  return drupal_get_form('commerce_customer_ui_customer_profile_type_delete_form', $profile_type);
}
