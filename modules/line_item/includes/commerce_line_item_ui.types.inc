<?php

/**
 * Menu callback: display an overview of available types.
 */
function commerce_line_item_ui_types_overview() {
  drupal_add_css(drupal_get_path('module', 'commerce_line_item') .'/theme/commerce_line_item.admin.css');

  $header = array(
    t('Name'),
    t('Operations'),
  );

  $rows = array();

  // Loop through all defined line item types.
  foreach (commerce_line_item_types() as $type => $line_item_type) {
    // Build the operation links for the current line item type.
    $type_arg = strtr($type, '_', '-');
    $links = menu_contextual_links('commerce-line-item-type', 'admin/commerce/config/line-items', array($type_arg));

    // Add the line item type's row to the table's rows array.
    $rows[] = array(
      theme('line_item_type_admin_overview', array('line_item_type' => $line_item_type)),
      theme('links', array('links' => $links, 'attributes' => array('class' => 'links inline operations'))),
    );
  }

  // If no line item types are defined...
  if (empty($rows)) {
    // Add a standard empty row with a link to add a new line item type.
    $rows[] = array(
      array(
        'data' => t('There are no line item types defined by enabled modules.'),
        'colspan' => 2,
      )
    );
  }

  return theme('table', array('header' => $header, 'rows' => $rows));
}

/**
 * Builds an overview of a line item type for display to an administrator.
 *
 * @param $variables
 *   An array of variables used to generate the display; by default includes the
 *     type key with a value of the line item type object.
 *
 * @ingroup themeable
 */
function theme_line_item_type_admin_overview($variables) {
  $line_item_type = $variables['line_item_type'];

  $output = check_plain($line_item_type['name']);
  $output .= ' <small>' . t('(Machine name: @type)', array('@type' => $line_item_type['type'])) . '</small>';

  if (!empty($line_item_type['description'])) {
    $output .= '<div class="description">' . filter_xss_admin($line_item_type['description']) . '</div>';
  }

  return $output;
}
