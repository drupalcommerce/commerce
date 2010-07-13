<?php
// $Id$

/**
 * @file
 */


/**
 * Form callback: edit the global order settings.
 */
function commerce_order_settings_form() {
  $form = array();

  $form['commerce_order_help_text'] = array(
    '#type' => 'textarea',
    '#title' => t('Order creation help text'),
    '#description' => t('Supply an optional help message to be displayed above the order add form.'),
    '#default_value' => variable_get('commerce_order_help_text', ''),
  );

  return system_settings_form($form);
}

/**
 * Form callback wrapper: create or edit an order.
 *
 * @param $order
 *   The order object to edit through the form.
 * @param $account
 *   For new orders, the customer's user account.
 * @param $breadcrumb
 *   TRUE or FALSE indicating whether or not to set the order breadcrumb.
 *
 * @return
 *   The form html to add or edit an order.
 */
function commerce_order_ui_order_form($order, $account = NULL, $breadcrumb = TRUE) {
  if ($breadcrumb) {
    commerce_order_ui_set_breadcrumb();
  }

  // Set the page title and a default customer if necessary.
  if (empty($order->order_id)) {
    drupal_set_title(t('Create an order'));

    if (!empty($account)) {
      $order->customer_uid = $account->uid;
    }
  }
  module_load_include('inc', 'commerce_order', 'includes/commerce_order.forms');
  return drupal_get_form('commerce_order_order_form', $order);
}

/**
 * Confirmation form for deleting an order.
 *
 * @param $order
 *   The order object to delete through the form.
 * @param $breadcrumb
 *   TRUE or FALSE indicating whether or not to set the order breadcrumb.
 *
 * @return
 *   The form html to add or edit an order.
 */
function commerce_order_ui_order_delete_form($order, $breadcrumb = TRUE) {
  if ($breadcrumb) {
    commerce_order_ui_set_breadcrumb();
  }
  module_load_include('inc', 'commerce_order', 'includes/commerce_order.forms');
  return drupal_get_form('commerce_order_order_delete_form', $order);
}
