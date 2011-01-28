<?php
// $Id$

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
    'needs-data' => 'payment',
  );

  // Tokens for payments.
  $payment = array();
  $payment['transaction-id'] = array(
    'name' => t('Transaction ID'),
    'description' => t('The primary identifier for a payment transaction.'),
  );
  $payment['revision-id'] = array(
    'name' => t('Revision ID'),
    'description' => t('The unique ID for the payment latest revision.'),
  );
  $payment['payment-method'] = array(
    'name' => t('Payment method'),
    'description' => t('The payment method method_id for the payment transaction.'),
  );
  $payment['payment-method-name'] = array(
    'name' => t('Payment method name'),
    'description' => t('The human-readable payment method method_id for the payment transaction.'),
  );
  $payment['instance-id'] = array(
    'name' => t('Instance ID'),
    'description' => t('The payment method instance ID for the payment transaction.'),
  );
  $payment['remote-id'] = array(
    'name' => t('Remote ID'),
    'description' => t('The remote identifier for the payment transaction.'),
  );
  $payment['message'] = array(
    'name' => t('Message'),
    'description' => t('The human-readable message associated to the payment transaction.'),
  );
  $payment['amount-raw'] = array(
    'name' => t('Raw amount'),
    'description' => t('The raw amount of the payment transaction.'),
  );
  $payment['amount-formatted'] = array(
    'name' => t('Formatted amount'),
    'description' => t('The formatted amount of the payment transaction.'),
  );
  $payment['currency-code'] = array(
    'name' => t('Currency code'),
    'description' => t('The currency code for the payment.'),
  );
  $payment['currency-code-symbol'] = array(
    'name' => t('Currency code symbol'),
    'description' => t('The currency code symbol for the payment.'),
  );
  $payment['status'] = array(
    'name' => t('Status'),
    'description' => t('The status of this transaction (pending, success, or failure).'),
  );
  $payment['remote-status'] = array(
    'name' => t('Remote status'),
    'description' => t('The status of the transaction at the payment provider.'),
  );
  $payment['payload'] = array(
    'name' => t('Payload'),
    'description' => t('he payment-gateway specific payload associated with this transaction.'),
  );
  // Chained tokens for payments.
  $payment['order'] = array(
    'name' => t('Order'),
    'description' => t('The order related with the payment.'),
    'type' => 'order',
  );
  $payment['owner'] = array(
    'name' => t('Owner'),
    'description' => t('The owner of the payment.'),
    'type' => 'user',
  );
  $payment['created'] = array(
    'name' => t('Date created'),
    'description' => t('The date the payment was created.'),
    'type' => 'date',
  );
  $payment['changed'] = array(
    'name' => t('Date changed'),
    'description' => t('The date the payment was last updated.'),
    'type' => 'date',
  );
  return array(
    'types' => array('payment' => $type),
    'tokens' => array('payment' => $payment),
  );
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

  if ($type == 'payment' && !empty($data['payment'])) {
    $payment = $data['payment'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'transaction-id':
          $replacements[$original] = $payment->transaction_id;
          break;

        case 'revision-id':
          $replacements[$original] = $payment->revision_id;
          break;

        case 'payment-method':
          $replacements[$original] = $sanitize ? check_plain($payment->payment_method) : $payment->payment_method;
          break;

        case 'payment-method-name':
          $payment_method_name = commerce_payment_method_load($payment->payment_method);
          $replacements[$original] = $sanitize ? check_plain($payment_method_name->title) : $payment_method_name->title;
          break;

        case 'instance-id':
          $replacements[$original] = $sanitize ? check_plain($payment->instance_id) : $payment->instance_id;
          break;

        case 'remote-id':
          $replacements[$original] = $sanitize ? check_plain($payment->remote_id) : $payment->remote_id;
          break;

        case 'message':
          $replacements[$original] = t($payment->message, is_array($payment->message_variables) ? $payment->message_variables : array());
          break;

        case 'amount-raw':
          $replacements[$original] = $sanitize ? check_plain($payment->amount) : $payment->amount;
          break;

        case 'amount-formatted':
          $replacements[$original] = commerce_currency_format($payment->amount, $payment->currency_code);
          break;

        case 'currency-code':
          $replacements[$original] = $sanitize ? check_plain($payment->currency_code) : $payment->currency_code;
          break;

        case 'currency-code-symbol':
          $replacements[$original] = commerce_currency_get_symbol($payment->currency_code);
          break;

        case 'status':
          $replacements[$original] = $sanitize ? check_plain($payment->status) : $payment->status;
          break;

        case 'remote-status':
          $replacements[$original] = $sanitize ? check_plain($payment->remote_status) : $payment->remote_status;
          break;

        case 'payload':
          $replacements[$original] = check_plain(print_r($payment->payload));
          break;

        // Default values for the chained tokens handled below.
        case 'order':
          if ($payment->order_id) {
            $order = commerce_order_load($payment->order_id);
            $replacements[$original] = $sanitize ? check_plain($order->order_number) : $order->order_number;
          }
          break;
        case 'owner':
          if ($order->uid == 0) {
            $name = variable_get('anonymous', t('Anonymous'));
          }
          else {
            $account = user_load($payment->uid);
            $name = $account->name;
          }
          $replacements[$original] = $sanitize ? filter_xss($name) : $name;
          break;
        case 'created':
          $replacements[$original] = format_date($payment->created, 'medium', '', NULL, $language_code);
          break;

        case 'changed':
          $replacements[$original] = format_date($payment->changed, 'medium', '', NULL, $language_code);
          break;
      }
    }

    if ($order_tokens = token_find_with_prefix($tokens, 'order')) {
      $order = commerce_order_load($payment->order_id);
      $replacements += token_generate('order', $order_tokens, array('order' => $order), $options);
    }

    if ($owner_tokens = token_find_with_prefix($tokens, 'owner')) {
      $owner = user_load($payment->uid);
      $replacements += token_generate('user', $owner_tokens, array('user' => $owner), $options);
    }

    foreach (array('created', 'changed') as $date) {
      if ($created_tokens = token_find_with_prefix($tokens, $date)) {
        $replacements += token_generate('date', $created_tokens, array('date' => $payment->{$date}), $options);
      }
    }
  }

  return $replacements;
}
