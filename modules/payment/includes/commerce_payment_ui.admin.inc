<?php

/**
 * @file
 * Administrative page callbacks for the Payment UI module.
 */


/**
 * Displays the payment transaction View in an order's payment tab.
 */
function commerce_payment_ui_order_tab($order) {
  // Set the breadcrumb and display the payments View.
  commerce_order_ui_set_breadcrumb();

  return commerce_embed_view('commerce_payment_order', 'defaults', array($order->order_id));
}

/**
 * Builds the payment settings page using the Rules UI overview table filtered
 *   to display payment method rules.
 */
function commerce_payment_ui_admin_page() {
  RulesPluginUI::$basePath = 'admin/commerce/config/payment-methods';
  $options = array('show plugin' => FALSE);

  $content['enabled']['title']['#markup'] = '<h3>' . t('Enabled payment method rules') . '</h3>';

  $conditions = array('event' => 'commerce_payment_methods', 'plugin' => 'reaction rule', 'active' => TRUE);
  $content['enabled']['rules'] = RulesPluginUI::overviewTable($conditions, $options);
  $content['enabled']['rules']['#empty'] = t('There are no active payment methods.');

  $content['disabled']['title']['#markup'] = '<h3>' . t('Disabled payment method rules') . '</h3>';

  $conditions['active'] = FALSE;
  $content['disabled']['rules'] = RulesPluginUI::overviewTable($conditions, $options);
  $content['disabled']['rules']['#empty'] = t('There are no disabled payment methods.');

  return $content;
}

/**
 * Displays the full details of a payment transaction.
 */
function commerce_payment_ui_payment_transaction_view($order, $transaction, $view_mode, $breadcrumb = TRUE) {
  // Set the breadcrumb for the appropriate view mode if specified.
  if ($breadcrumb) {
    commerce_payment_ui_set_order_breadcrumb($order, $view_mode);
  }

  return entity_view('commerce_payment_transaction', array($transaction->transaction_id => $transaction), $view_mode, NULL, TRUE);
}

/**
 * Form callback wrapper: confirmation form for deleting a payment transaction.
 *
 * @param $order
 *   The order object containing the transaction being deleted by the form.
 * @param $transaction
 *   The actual payment transaction that will be deleted.
 *
 * @see commerce_payment_payment_transaction_delete_form()
 */
function commerce_payment_ui_payment_transaction_delete_form_wrapper($order, $transaction) {
  // Add the breadcrumb for the form's location.
  commerce_payment_ui_set_order_breadcrumb($order);

  // Include the forms file from the Payment module.
  module_load_include('inc', 'commerce_payment', 'includes/commerce_payment.forms');

  return drupal_get_form('commerce_payment_ui_payment_transaction_delete_form', $order, $transaction);
}
