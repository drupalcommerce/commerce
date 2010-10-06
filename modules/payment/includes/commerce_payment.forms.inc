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

  // If a payment method has already been selected...
  if (!empty($form_state['payment_method'])) {
    $payment_method = $form_state['payment_method'];

    // Look for a transaction in progress to use for defaults.
    $transaction = !empty($form_state['transaction']) ? $form_state['transaction'] : FALSE;

    $form['payment_terminal'] = array(
      '#type' => 'fieldset',
      '#title' => t('Payment terminal: @title', array('@title' => $payment_method->title)),
      '#attributes' => array('class' => array('payment-terminal')),
    );

    // Establish defaults for the amount if possible.
    if ($transaction) {
      $default_amount = $transaction->amount;
      $default_currency_code = $transaction->currency_code;
    }
    elseif ($balance = commerce_payment_order_balance($order)) {
      $default_amount = $balance['amount'] > 0 ? $balance['amount'] : '';
      $default_currency_code = $balance['currency_code'];
    }
    else {
      $default_amount = '';
      $default_currency_code = variable_get('commerce_default_currency', 'USD');
    }

    $form['payment_terminal']['amount'] = array(
      '#type' => 'textfield',
      '#title' => t('Amount'),
      '#default_value' => $default_amount,
      '#size' => 10,
      '#prefix' => '<div class="payment-terminal-amount">',
    );

    // Build a currency options list from all enabled currencies.
    $options = array();

    foreach (commerce_currencies(TRUE) as $currency_code => $currency) {
      $options[$currency_code] = check_plain($currency->code);

      if (!empty($currency->symbol)) {
        $options[$currency_code] .= ' - ' . check_plain($currency->symbol);
      }
    }

    $form['payment_terminal']['currency_code'] = array(
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $default_currency_code,
      '#suffix' => '</div>',
    );

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );
  }
  else {
    // Otherwise present the payment method selection form.
    $form['payment_method'] = array(
      '#type' => 'select',
      '#options' => $options,
      '#prefix' => '<div class="add-payment">',
    );

    $form['add_payment'] = array(
      '#type' => 'submit',
      '#value' => t('Add payment'),
      '#suffix' => '</div>',
      '#ajax' => array(
        'callback' => 'commerce_payment_order_transaction_add_form_add_refresh',
        'wrapper' => 'commerce-payment-order-transaction-add-form',
      ),
    );
  }

  return $form;
}

/**
 * Returns the full payment terminal form when a payment method is selected.
 */
function commerce_payment_order_transaction_add_form_add_refresh($form, $form_state) {
  return $form;
}

function commerce_payment_order_transaction_add_form_submit($form, &$form_state) {
  // If the "Add payment" button was clicked...
  if ($form_state['values']['op'] == t('Add payment')) {
    // Store the payment method in the form state and rebuild the form.
    $form_state['payment_method'] = commerce_payment_method_load($form_state['values']['payment_method']);
    $form_state['rebuild'] = TRUE;
  }
  else {
    // Build the basic transaction object.
    $transaction = commerce_payment_transaction_new($form_state['payment_method']->method_id, $form_state['order']->order_id);
    $transaction->amount = $form_state['values']['amount'];
    $transaction->currency_code = $form_state['values']['currency_code'];
    $transaction->status = COMMERCE_PAYMENT_STATUS_PENDING;

    // If the payment terminal passes validation...
    // TODO: Pass the transaction and form data on to the payment method's
    // callback for processing the payment.
    if (TRUE) {
      commerce_payment_transaction_save($transaction);

      drupal_set_message(t('Payment transaction created.'));
    }
    else {
      // Store the transaction in the form state and rebuild the form.
      $form_state['transaction'] = $transaction;
      $form_state['rebuild'] = TRUE;
    }
  }
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
