<?php

/**
 * @file
 * Builds placeholder replacement tokens for payment-related data.
 */

/**
 * Implements hook_token_info().
 */
function commerce_payment_token_info() {
  $type = array(
    'name' => t('Payment transactions', array(), array('context' => 'a drupal commerce payment transaction')),
    'description' => t('Tokens related to payment transactions.'),
    'needs-data' => 'commerce-payment-transaction',
  );

  // Tokens for payments.
  $transaction = array();

  $transaction['transaction-id'] = array(
    'name' => t('Transaction ID'),
    'description' => t('The primary identifier for a payment transaction.'),
  );
  $transaction['revision-id'] = array(
    'name' => t('Revision ID'),
    'description' => t('The unique ID for the latest revision of a payment transaction.'),
  );
  $transaction['payment-method'] = array(
    'name' => t('Payment method'),
    'description' => t('The payment method method_id for the payment transaction.'),
  );
  $transaction['payment-method-title'] = array(
    'name' => t('Payment method title'),
    'description' => t('The administrative title of the payment method for the payment transaction.'),
  );
  $transaction['payment-method-short-title'] = array(
    'name' => t('Payment method short title'),
    'description' => t('The customer oriented short title of the payment method for the payment transaction.'),
  );
  $transaction['remote-id'] = array(
    'name' => t('Remote ID'),
    'description' => t('The remote identifier for the payment transaction.'),
  );
  $transaction['message'] = array(
    'name' => t('Message'),
    'description' => t('The human-readable message associated to the payment transaction.'),
  );
  $transaction['amount-raw'] = array(
    'name' => t('Raw amount'),
    'description' => t('The raw amount of the payment transaction.'),
  );
  $transaction['amount-formatted'] = array(
    'name' => t('Formatted amount'),
    'description' => t('The formatted amount of the payment transaction.'),
  );
  $transaction['currency-code'] = array(
    'name' => t('Currency code'),
    'description' => t('The currency code for the payment.'),
  );
  $transaction['currency-symbol'] = array(
    'name' => t('Currency symbol'),
    'description' => t('The currency symbol for the payment.'),
  );
  $transaction['status'] = array(
    'name' => t('Status'),
    'description' => t('The status of this transaction (pending, success, or failure).'),
  );
  $transaction['remote-status'] = array(
    'name' => t('Remote status'),
    'description' => t('The status of the transaction at the payment provider.'),
  );

  // Chained tokens for payment transactions.
  $transaction['order'] = array(
    'name' => t('Order'),
    'description' => t('The order related to the payment transaction.'),
    'type' => 'commerce-order',
  );
  $transaction['creator'] = array(
    'name' => t('Creator'),
    'description' => t('The creator of the payment transaction.'),
    'type' => 'user',
  );
  $transaction['created'] = array(
    'name' => t('Date created'),
    'description' => t('The date the payment transaction was created.'),
    'type' => 'date',
  );
  $transaction['changed'] = array(
    'name' => t('Date changed'),
    'description' => t('The date the payment transaction was last updated.'),
    'type' => 'date',
  );
  return array(
    'types' => array('commerce-payment-transaction' => $type),
    'tokens' => array('commerce-payment-transaction' => $transaction),
  );
}

/**
 * Implements hook_token_info_alter().
 */
function commerce_payment_token_info_alter(&$data) {
  if (module_exists('commerce_checkout')) {
    $data['tokens']['commerce-order']['payment-method'] = array(
      'name' => t('Payment method'),
      'description' => t('The method_id of the payment method selected by the customer during checkout.'),
    );
    $data['tokens']['commerce-order']['payment-method-title'] = array(
      'name' => t('Payment method title'),
      'description' => t('The administrative title of the payment method selected by the customer during checkout.'),
    );
    $data['tokens']['commerce-order']['payment-method-short-title'] = array(
      'name' => t('Payment method short title'),
      'description' => t('The customer oriented short title of the payment method selected by the customer during checkout.'),
    );

  }
}

/**
 * Implements hook_tokens().
 */
function commerce_payment_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $url_options = array('absolute' => TRUE);

  if (isset($options['language'])) {
    $url_options['language'] = $options['language'];
    $language_code = $options['language']->language;
  }
  else {
    $language_code = NULL;
  }

  $sanitize = !empty($options['sanitize']);

  $replacements = array();

  if ($type == 'commerce-payment-transaction' && !empty($data['commerce-payment-transaction'])) {
    $transaction = $data['commerce-payment-transaction'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'transaction-id':
          $replacements[$original] = $transaction->transaction_id;
          break;

        case 'revision-id':
          $replacements[$original] = $transaction->revision_id;
          break;

        case 'payment-method':
          $replacements[$original] = $sanitize ? check_plain($transaction->payment_method) : $transaction->payment_method;
          break;

        case 'payment-method-title':
          $title = commerce_payment_method_get_title('title', $transaction->payment_method);
          $replacements[$original] = $sanitize ? check_plain($title) : $title;
          break;

        case 'payment-method-short-title':
          $title = commerce_payment_method_get_title('short_title', $transaction->payment_method);
          $replacements[$original] = $sanitize ? check_plain($title) : $title;
          break;

        case 'remote-id':
          $replacements[$original] = $sanitize ? check_plain($transaction->remote_id) : $transaction->remote_id;
          break;

        case 'message':
          $replacements[$original] = t($transaction->message, is_array($transaction->message_variables) ? $transaction->message_variables : array());
          break;

        case 'amount-raw':
          $replacements[$original] = $sanitize ? check_plain($transaction->amount) : $transaction->amount;
          break;

        case 'amount-formatted':
          $replacements[$original] = commerce_currency_format($transaction->amount, $transaction->currency_code);
          break;

        case 'currency-code':
          $replacements[$original] = $sanitize ? check_plain($transaction->currency_code) : $transaction->currency_code;
          break;

        case 'currency-symbol':
          $replacements[$original] = commerce_currency_get_symbol($transaction->currency_code);
          break;

        case 'status':
          $transaction_status = commerce_payment_transaction_status_load($transaction->status);
          $replacements[$original] = $sanitize ? check_plain($transaction_status['title']) : $transaction_status['title'];
          break;

        case 'remote-status':
          $replacements[$original] = $sanitize ? check_plain($transaction->remote_status) : $transaction->remote_status;
          break;

        // Default values for the chained tokens handled below.
        case 'order':
          if ($transaction->order_id) {
            $order = commerce_order_load($transaction->order_id);
            $replacements[$original] = $sanitize ? check_plain($order->order_number) : $order->order_number;
          }
          break;

        case 'creator':
          if ($transaction->uid == 0) {
            $name = variable_get('anonymous', t('Anonymous'));
          }
          else {
            $creator = user_load($transaction->uid);
            $name = $creator->name;
          }
          $replacements[$original] = $sanitize ? filter_xss($name) : $name;
          break;

        case 'created':
          $replacements[$original] = format_date($transaction->created, 'medium', '', NULL, $language_code);
          break;

        case 'changed':
          $replacements[$original] = format_date($transaction->changed, 'medium', '', NULL, $language_code);
          break;
      }
    }

    if ($order_tokens = token_find_with_prefix($tokens, 'order')) {
      $order = commerce_order_load($transaction->order_id);
      $replacements += token_generate('commerce-order', $order_tokens, array('commerce-order' => $order), $options);
    }

    if ($creator_tokens = token_find_with_prefix($tokens, 'creator')) {
      $creator = user_load($transaction->uid);
      $replacements += token_generate('user', $creator_tokens, array('user' => $creator), $options);
    }

    foreach (array('created', 'changed') as $date) {
      if ($created_tokens = token_find_with_prefix($tokens, $date)) {
        $replacements += token_generate('date', $created_tokens, array('date' => $transaction->{$date}), $options);
      }
    }
  }

  if ($type == 'commerce-order' && !empty($data['commerce-order'])) {
    $order = $data['commerce-order'];

    if (!empty($order->data['payment_method'])) {
      $payment_method = commerce_payment_method_instance_load($order->data['payment_method']);
      $method_id = $payment_method['method_id'];

      foreach ($tokens as $name => $original) {
        switch ($name) {
          case 'payment-method':
            $replacements[$original] = $sanitize ? check_plain($method_id) : $method_id;
            break;

          case 'payment-method-title':
            $title = commerce_payment_method_get_title('title', $method_id);
            $replacements[$original] = $sanitize ? check_plain($title) : $title;
            break;

          case 'payment-method-short-title':
            $title = commerce_payment_method_get_title('short_title', $method_id);
            $replacements[$original] = $sanitize ? check_plain($title) : $title;
            break;
        }
      }
    }
    else {
      foreach ($tokens as $name => $original) {
        switch ($name) {
          case 'payment-method':
          case 'payment-method-title':
          case 'payment-method-short-title':
            $replacements[$original] = t('Unknown');
            break;
        }
      }
    }
  }

  return $replacements;
}
