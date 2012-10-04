<?php

/**
 * @file
 * Page callbacks and form builder functions for administering customer profiles.
 */


/**
 * Menu callback: display a list of customer profile types that the user can create.
 */
function commerce_customer_ui_customer_profile_add_page() {
  $item = menu_get_item();
  $content = system_admin_menu_block($item);

  // Bypass the admin/commerce/customers/profiles/add listing if only one
  // customer profile available.
  if (count($content) == 1) {
    $item = array_shift($content);
    drupal_goto($item['href']);
  }

  return theme('customer_profile_add_list', array('content' => $content));
}

/**
 * Displays the list of available customer profile types for profile creation.
 *
 * @ingroup themeable
 */
function theme_customer_profile_add_list($variables) {
  $content = $variables['content'];
  $output = '';

  if ($content) {
    $output = '<dl class="commerce-customer-profile-type-list">';
    foreach ($content as $item) {
      $output .= '<dt>' . l($item['title'], $item['href'], $item['localized_options']) . '</dt>';
      $output .= '<dd>' . filter_xss_admin($item['description']) . '</dd>';
    }
    $output .= '</dl>';
  }
  else {
    if (user_access('administer customer profile types')) {
      $output = '<p>' . t('You have not enabled modules defining any customer profile types yet.') . '</p>';
    }
    else {
      $output = '<p>' . t('No customer profile types have been enabled yet for you to use.') . '</p>';
    }
  }

  return $output;
}

/**
 * Form callback wrapper: create or edit a customer profile.
 *
 * @param $profile
 *   The customer profile object being edited by this form.
 *
 * @see commerce_customer_customer_profile_form()
 */
function commerce_customer_ui_customer_profile_form_wrapper($profile) {
  // Include the forms file from the Customer module.
  module_load_include('inc', 'commerce_customer', 'includes/commerce_customer_profile.forms');
  return drupal_get_form('commerce_customer_ui_customer_profile_form', $profile);
}

/**
 * Form callback wrapper: confirmation form for deleting a customer profile.
 *
 * @param $profile
 *   The customer profile object being deleted by this form.
 *
 * @see commerce_customer_customer_profile_delete_form()
 */
function commerce_customer_ui_customer_profile_delete_form_wrapper($profile) {
  // Include the forms file from the Customer module.
  module_load_include('inc', 'commerce_customer', 'includes/commerce_customer_profile.forms');
  return drupal_get_form('commerce_customer_ui_customer_profile_delete_form', $profile);
}
