<?php

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
  $form_state['build_info']['files']['form'] = drupal_get_path('module', 'commerce_payment') . '/includes/commerce_payment.forms.inc';

  // Store the initial order in the form state.
  $form_state['order'] = $order;

  $form['#access'] = commerce_payment_transaction_order_access('create', $order);

  // If a payment method has already been selected...
  if (!empty($form_state['payment_method'])) {
    $payment_method = $form_state['payment_method'];

    $form['payment_terminal'] = array(
      '#type' => 'fieldset',
      '#title' => t('Payment terminal: @title', array('@title' => $payment_method['title'])),
      '#attributes' => array('class' => array('payment-terminal')),
      '#element_validate' => array('commerce_payment_order_transaction_add_form_payment_terminal_validate'),
    );

    // Establish defaults for the amount if possible.
    if ($balance = commerce_payment_order_balance($order)) {
      $default_amount = $balance['amount'] > 0 ? $balance['amount'] : '';
      $default_currency_code = $balance['currency_code'];

      // Convert the default amount to an acceptable textfield value.
      $default_amount = commerce_currency_amount_to_decimal($default_amount, $default_currency_code);

      $currency = commerce_currency_load($default_currency_code);
      $default_amount = number_format($default_amount, $currency['decimals'], '.', '');
    }
    else {
      $default_amount = '';
      $default_currency_code = commerce_default_currency();
    }

    $form['payment_terminal']['amount'] = array(
      '#type' => 'textfield',
      '#title' => t('Amount'),
      '#default_value' => $default_amount,
      '#required' => TRUE,
      '#size' => 10,
      '#prefix' => '<div class="payment-terminal-amount">',
    );

    // Build a currency options list from all enabled currencies.
    $options = array();

    foreach (commerce_currencies(TRUE) as $currency_code => $currency) {
      $options[$currency_code] = check_plain($currency['code']);
    }

    $form['payment_terminal']['currency_code'] = array(
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $default_currency_code,
      '#suffix' => '</div>',
    );

    // Find the values already submitted for the payment terminal.
    $terminal_values = !empty($form_state['values']['payment_details']) ? $form_state['values']['payment_details'] : array();

    if ($callback = commerce_payment_method_callback($payment_method, 'submit_form')) {
      $form['payment_terminal']['payment_details'] = $callback($payment_method, $terminal_values, NULL, $order);
    }
    else {
      $form['payment_terminal']['payment_details'] = array();
    }

    $form['payment_terminal']['payment_details']['#tree'] = TRUE;

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );
  }
  else {
    // Otherwise present the payment method selection form.
    $event = rules_get_cache('event_commerce_payment_methods');

    // Build an options array of all available payment methods that can setup
    // transactions using the local terminal. If there is more than one instance
    // of any payment method available on site, list them in optgroups using the
    // payment method title.
    $instances = array();
    $options = array();
    $optgroups = FALSE;

    // Only build the options array if payment method Rules are enabled.
    if (!empty($event)) {
      foreach (commerce_payment_methods() as $method_id => $payment_method) {
        // Only check payment methods that should appear on the terminal.
        if ($payment_method['terminal']) {
          // Look for a Rule enabling this payment method.
          foreach ($event->getIterator() as $rule) {
            foreach ($rule->actions() as $action) {
              // If an action is found, add its instance to the options array.
              if ($action->getElementName() == 'commerce_payment_enable_' . $method_id) {
                $instances[check_plain($payment_method['title'])][] = array(
                  'instance_id' => commerce_payment_method_instance_id($method_id, $rule),
                  'label' => check_plain($rule->label()),
                );

                // If this is the second instance for this payment method, turn
                // on optgroups.
                if (count($instances[check_plain($payment_method['title'])]) > 1) {
                  $optgroups = TRUE;
                }
              }
            }
          }
        }
      }

      // Build an options array based on whether or not optgroups are necessary.
      foreach ($instances as $optgroup => $values) {
        foreach ($values as $value) {
          if ($optgroups) {
            $options[$optgroup][$value['instance_id']] = $value['label'];
          }
          else {
            $options[$value['instance_id']] = $value['label'];
          }
        }
      }
    }

    if (!empty($options)) {
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
    else {
      $form['payment_method'] = array(
        '#markup' => t('No payment methods available to add payments.'),
      );
    }
  }

  return $form;
}

/**
 * Returns the full payment terminal form when a payment method is selected.
 */
function commerce_payment_order_transaction_add_form_add_refresh($form, $form_state) {
  return $form;
}

/**
 * Validation callback for the payment terminal to check the amount data type
 *   and convert it to a proper integer amount on input.
 */
function commerce_payment_order_transaction_add_form_payment_terminal_validate($element, &$form_state) {
  // If a payment method has already been selected...
  if (!empty($form_state['payment_method']) && !empty($form_state['values']['amount'])) {
    if (!is_numeric($form_state['values']['amount'])) {
      form_set_error('amount', t('You must enter a numeric amount value.'));
    }
    else {
      form_set_value($element['amount'], commerce_currency_decimal_to_amount($form_state['values']['amount'], $form_state['values']['currency_code']), $form_state);
    }
  }
}

/**
 * Validation callback for commerce_payment_order_transaction_add_form().
 */
function commerce_payment_order_transaction_add_form_validate($form, &$form_state) {
  // If the button used to submit was not the "Add payment" button, give the
  // payment method a chance to validate the input.
  if (end($form_state['triggering_element']['#array_parents']) != 'add_payment') {
    $payment_method = $form_state['payment_method'];
    $order = $form_state['order'];

    // Find out if the payment details are valid before attempting to process.
    if ($callback = commerce_payment_method_callback($payment_method, 'submit_form_validate')) {
      $callback($payment_method, $form['payment_terminal']['payment_details'], $form_state['values']['payment_details'], $order, array('payment_details'));
    }
  }
}

/**
 * Submit callback for commerce_payment_order_transaction_add_form().
 */
function commerce_payment_order_transaction_add_form_submit($form, &$form_state) {
  // If the "Add payment" button was clicked...
  if (end($form_state['triggering_element']['#array_parents']) == 'add_payment') {
    // Store the payment method in the form state and rebuild the form.
    $form_state['payment_method'] = commerce_payment_method_instance_load($form_state['values']['payment_method']);
    $form_state['rebuild'] = TRUE;
  }
  else {
    $payment_method = $form_state['payment_method'];
    $order = $form_state['order'];

    // Delegate submit to the payment method callback.
    if ($callback = commerce_payment_method_callback($payment_method, 'submit_form_submit')) {
      $charge = array(
        'amount' => $form_state['values']['amount'],
        'currency_code' => $form_state['values']['currency_code'],
      );

      $details_form = !empty($form['payment_terminal']['payment_details']) ? $form['payment_terminal']['payment_details'] : array();
      $details_values = !empty($form_state['values']['payment_details']) ? $form_state['values']['payment_details'] : array();

      $result = $callback($payment_method, $details_form, $details_values, $order, $charge);

      if ($result === FALSE) {
        $form_state['rebuild'] = TRUE;
      }
      else {
        drupal_set_message(t('Payment transaction created.'));
      }
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
  $form_state['build_info']['files']['form'] = drupal_get_path('module', 'commerce_payment') . '/includes/commerce_payment.forms.inc';

  $form['#submit'][] = 'commerce_payment_payment_transaction_delete_form_submit';

  $form = confirm_form($form,
    t('Are you sure you want to delete this transaction?'),
    '',
    '<p>' . t('@amount paid via %method on @date. Deleting this transaction cannot be undone.', array('@amount' => commerce_currency_format($transaction->amount, $transaction->currency_code), '%method' => $payment_method['title'], '@date' => format_date($transaction->created, 'short'))) . '</p>',
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
