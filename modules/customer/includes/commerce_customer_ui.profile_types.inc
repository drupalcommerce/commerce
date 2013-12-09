<?php

/**
 * @file
 */


/**
 * Menu callback: display an overview of available types.
 */
function commerce_customer_ui_customer_profile_types_overview() {
  drupal_add_css(drupal_get_path('module', 'commerce_customer') . '/theme/commerce_customer.admin.css');

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
    // Add a standard empty row.
    $rows[] = array(
      array(
        'data' => t('There are no customer profile types defined on this site.'),
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

  $output = check_plain($profile_type['name']);
  $output .= ' <small>' . t('(Machine name: @type)', array('@type' => $profile_type['type'])) . '</small>';
  $output .= '<div class="description">' . filter_xss_admin($profile_type['description']) . '</div>';

  return $output;
}
