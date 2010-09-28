<?php
// $Id$

/**
 * @file
 * Defines forms for creating payment transactions.
 */


/**
 * Allows an administrator to choose a payment method type and add a transaction
 *  for a specific order.
 *
 * @param $order
 *   The order to add the transaction to.
 */
function commerce_payment_order_transaction_add_form($form, &$form_state, $order) {
  // Ensure this include file is loaded when the form is rebuilt from the cache.
  $form_state['build_info']['file'] = drupal_get_path('module', 'commerce_payment') . '/includes/commerce_payment.forms.inc';

  // Store the initial order in the form state.
  $form_state['order'] = $order;

  // Build an options array of all available payment methods.
  $options = array();

  foreach (commerce_payment_methods() as $method_id => $payment_method) {
    $options[$method_id] = check_plain($payment_method->title);
  }

  $form['payment_method'] = array(
    '#type' => 'select',
    '#options' => $options,
    '#prefix' => '<div class="add-payment">',
  );

  $form['add_payment'] = array(
    '#type' => 'submit',
    '#value' => t('Add payment'),
    '#suffix' => '</div>',
  );

  return $form;
}
