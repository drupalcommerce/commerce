<?php
// $Id$

/**
 * Payment pane: form callback.
 */
function commerce_payment_pane_form($pane_values, $checkout_pane, $order) {
  $form = array();

  // Invoke the payment methods event that will populate the order with
  // available payment methods.
  $order->payment_methods = array();
  rules_invoke_event('commerce_payment_methods', $order);

  $payment_method_options = array();
  if (!empty($order->payment_methods)) {
    foreach ($order->payment_methods as $payment_method) {
      $payment_method_info = commerce_payment_method($payment_method['method_id']);
      if ($payment_method_info) {
        $payment_method_options[] = $payment_method_info['title'];
      }
    }
  }

  if ($payment_method_options) {
    $form['payment_method'] = array(
      '#type' => 'radios',
      '#options' => $payment_method_options,
    );

    if (isset($order->payment_method)) {
      $form['payment_method']['#default_value'] = $order->payment_method;
    }
    else {
      // Select the first element.
      reset($payment_method_options);
      $form['payment_method']['#default_value'] = key($payment_method_options);
    }
  }

  return $form;
}

/**
 * Payment pane: validation callback.
 */
function commerce_payment_pane_form_validate($form, &$form_state, $order) {
  $order->payment_method = $form_state['values']['commerce_payment']['payment_method'];
}

/**
 * Payment pane: submit callback.
 */
function commerce_payment_pane_form_submit($form, &$form_state, $order) {
  $order->payment_method = $form_state['values']['commerce_payment']['payment_method'];
}
