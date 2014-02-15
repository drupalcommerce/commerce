<?php

/**
 * @file
 * Page callbacks and form builder functions for administering orders.
 */


/**
 * Form callback: edit the global order settings.
 */
function commerce_order_settings_form($form, &$form_state) {
  $form['commerce_order_help_text'] = array(
    '#type' => 'textarea',
    '#title' => t('Order creation help text'),
    '#description' => t('Supply an optional help message to be displayed above the order add form.'),
    '#default_value' => variable_get('commerce_order_help_text', ''),
    '#weight' => -10,
  );
  $form['commerce_order_auto_revision'] = array(
    '#type' => 'checkbox',
    '#title' => t('Create new revisions when orders are updated by default.'),
    '#description' => t('This default may be overridden on the order edit form but will always be respected for other order status updates.'),
    '#default_value' => variable_get('commerce_order_auto_revision', TRUE),
    '#weight' => 0,
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
 *
 * @see commerce_order_order_form()
 */
function commerce_order_ui_order_form_wrapper($order, $account = NULL) {
  // Set the page title and a default customer if necessary.
  if (empty($order->order_id)) {
    drupal_set_title(t('Create an order'));

    if (!empty($account)) {
      $order->uid = $account->uid;
    }
  }

  // Include the forms file from the Order module.
  module_load_include('inc', 'commerce_order', 'includes/commerce_order.forms');
  return drupal_get_form('commerce_order_ui_order_form', $order);
}

/**
 * Form callback wrapper: confirmation form for deleting an order.
 *
 * @param $order
 *   The order object to delete through the form.
 *
 * @see commerce_order_order_delete_form()
 */
function commerce_order_ui_order_delete_form_wrapper($order) {
  // Include the forms file from the Order module.
  module_load_include('inc', 'commerce_order', 'includes/commerce_order.forms');
  return drupal_get_form('commerce_order_ui_order_delete_form', $order);
}
