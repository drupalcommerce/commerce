<?php
// $Id$

/**
 * @file
 * Defines forms for creating and administering payment transactions.
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

/**
 * Form callback: confirmation form for deleting a transaction.
 *
 * @param $transaction
 *   The payment transaction object to be deleted.
 *
 * @see confirm_form()
 */
function commerce_payment_payment_transaction_delete_form($form, &$form_state, $order, $transaction) {
  $form_state['order'] = $order;
  $form_state['transaction'] = $transaction;

  // Load and store the payment method.
  $payment_method = commerce_payment_method_load($transaction->payment_method);
  $form_state['payment_method'] = $payment_method;

  // Ensure this include file is loaded when the form is rebuilt from the cache.
  $form_state['build_info']['file'] = drupal_get_path('module', 'commerce_payment') . '/includes/commerce_payment.forms.inc';

  $form['#submit'][] = 'commerce_payment_payment_transaction_delete_form_submit';

  $form = confirm_form($form,
    t('Are you sure you want to delete this transaction?'),
    '',
    '<p>' . t('@amount paid via %method on @date. Deleting this transaction cannot be undone.', array('@amount' => commerce_currency_format($transaction->amount, $transaction->currency_code), '%method' => $payment_method->title, '@date' => format_date($transaction->created, 'short'))) . '</p>',
    t('Delete'),
    t('Cancel'),
    'confirm'
  );

  return $form;
}

/**
 * Submit callback for commerce_payment_transaction_delete_form().
 */
function commerce_payment_payment_transaction_delete_form_submit($form, &$form_state) {
  $transaction = $form_state['transaction'];

  if (commerce_payment_transaction_delete($transaction->transaction_id)) {
    drupal_set_message(t('Payment transaction deleted.'));
    watchdog('commerce_payment', 'Deleted payment transaction @transaction.', array('@transaction' => $transaction->transaction_id), WATCHDOG_NOTICE);
  }
  else {
    drupal_set_message(t('The payment transaction could not be deleted.'), 'error');
  }
}
