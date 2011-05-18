<?php

/**
 * @file
 * Hooks provided by the Line Item module.
 */


/**
 * Defines links for use in line item summary area handlers on Views.
 *
 * The line item summary area handler totals the value of the various line items
 * in a View and optionally includes a set of links. These are used in the core
 * shopping cart block View to let the user browse straight to the shopping cart
 * form or the checkout form. The format of the return value is a links array as
 * required by theme_links() with the addition of a weight parameter used to
 * sort the links prior to display.
 *
 * @return
 *   An associative array of link arrays keyed by link names, with the names
 *   being appended to the class of each link's list item when rendered by
 *   theme_links(). Link arrays should include the following key / value
 *   properties expected by theme_links():
 *   - title: the link text
 *   - href: the link URL; if ommitted, the link is rendered as plain text
 *   - html: boolean indicating whether or not the link text should be rendered
 *     as HTML or escaped; defaults to FALSE
 *   - weight: custom to this hook, the weight property is an integer value used
 *     to sort links prior to rendering; defaults to 0
 *   - access: custom to this hook, a boolean value indicating whether or not
 *     the current user has access to the link; defaults to TRUE
 *   The full link array will be passed to theme_link(), meaning any additional
 *   properties can be included as desired (such as the attributes array as
 *   demonstrated below).
 *
 * @see commerce_line_item_summary_links()
 * @see commerce_cart_commerce_line_item_summary_link_info()
 * @see theme_links()
 */
function hook_commerce_line_item_summary_link_info() {
  return array(
    'view_cart' => array(
      'title' => t('View cart'),
      'href' => 'cart',
      'attributes' => array('rel' => 'nofollow'),
      'weight' => 0,
    ),
    'checkout' => array(
      'title' => t('Checkout'),
      'href' => 'checkout',
      'attributes' => array('rel' => 'nofollow'),
      'weight' => 5,
      'access' => user_access('access checkout'),
    ),
  );
}

/**
 * Allows you to alter line item summary links.
 *
 * @param $links
 *   Array of line item summary links keyed by name exposed by
 *   hook_commerce_line_item_summary_link_info() implementations.
 *
 * @see hook_commerce_line_item_summary_link_info()
 */
function hook_commerce_line_item_summary_link_info_alter(&$links) {
  // Alter the weight of the checkout link to display before the view cart link.
  if (!empty($links['checkout'])) {
    $links['checkout']['weight'] = -5;
  }
}

/**
 * Allows you to prepare line item data before it is saved on insert or update.
 *
 * @param $line_item
 *   The line item object to be saved.
 *
 * @see rules_invoke_all()
 */
function hook_commerce_line_item_presave_insert(&$line_item) {
  // No example.
}
