<?php

/**
 * Export Drupal Commerce orders to Views.
 */

/**
 * Implements hook_views_data()
 */
function commerce_order_ui_views_data() {
  $data = array();

  // Define an area handler that displays a "View order" redirect form.
  $data['views']['view_order_form'] = array(
    'title' => t('View order form'),
    'help' => t('Display a form that redirects to the view page for an order.'),
    'area' => array(
      'handler' => 'commerce_order_ui_handler_area_view_order_form'
    ),
  );

  return $data;
}
