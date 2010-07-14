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

  // Generate the list of payment method options.
  $payment_method_options = array();
  if (!empty($order->payment_methods)) {
    foreach ($order->payment_methods as $payment_method) {
      $payment_method_info = commerce_payment_method($payment_method['method_id']);
      if ($payment_method_info) {
        $payment_method_options[] = $payment_method_info['title'];
      }
    }
  }

  // Store the payment methods in the form for validation purposes.
  $form['payment_methods'] = array(
    '#type' => 'value',
    '#value' => $order->payment_methods,
  );

  // Display the payment method selection form.
  if ($payment_method_options) {
    $form['payment_method'] = array(
      '#type' => 'radios',
      '#options' => $payment_method_options,
    );

    if (isset($order->data['payment_method'])) {
      $form['payment_method']['#default_value'] = $order->data['payment_method'];
    }
    else if (isset($pane_values['payment_method'])) {
      $form['payment_method']['#default_value'] = $pane_values['payment_method'];
    }
    else {
      // Select the first element.
      reset($payment_method_options);
      $form['payment_method']['#default_value'] = key($payment_method_options);
    }
  }

  // Display the payment-method form.
  $current_payment_method = $order->payment_methods[$form['payment_method']['#default_value']];
  $payment_method_info = commerce_payment_method($current_payment_method['method_id']);
  if (function_exists($callback = $payment_method_info['callbacks']['submit_form'])) {
    $form['payment'] = $callback($current_payment_method, $pane_values, $checkout_pane, $order);
  }

  return $form;
}

/**
 * Payment pane: validation callback.
 */
function commerce_payment_pane_form_validate($pane_form, $pane_values, $order) {
  if ($pane_values['payment_method'] != $pane_form['payment_method']['#default_value']) {
    // Always rebuild the pane if the method changed.
    // TODO: do not rebuild if the payment method doesn't have a form callback.
    return FALSE;
  }

  // Delegate validation to the payment method callback.
  $current_payment_method = $pane_values['payment_methods'][$pane_values['payment_method']];
  $payment_method_info = commerce_payment_method($current_payment_method['method_id']);
  if (function_exists($callback = $payment_method_info['callbacks']['submit_form_validate'])) {
    return $callback($current_payment_method, $pane_form['payment'], $pane_values['payment'], $order);
  }

  // Nothing to validate.
  return TRUE;
}

/**
 * Payment pane: submit callback.
 */
function commerce_payment_pane_form_submit($pane_form, $pane_values, $order) {
  $order->data['payment_method'] = $pane_values['payment_method'];

  // Delegate submit to the payment method callback.
  $current_payment_method = $pane_values['payment_methods'][$pane_values['payment_method']];
  $payment_method_info = commerce_payment_method($current_payment_method['method_id']);
  if (function_exists($callback = $payment_method_info['callbacks']['submit_form_submit'])) {
    return $callback($current_payment_method, $pane_form['payment'], $pane_values['payment'], $order);
  }
}

/**
 * Payment redirect pane: form callback.
 */
function commerce_payment_redirect_pane_form($pane_values, $checkout_pane, $order) {
  // Delegate to the payment method.
  $current_payment_method = $order->payment_methods[$order->payment_method];
  $payment_method_info = commerce_payment_method($current_payment_method['method_id']);
  if (function_exists($callback = $payment_method_info['callbacks']['redirect_form'])) {
    $form = $callback($current_payment_method, $pane_values, $checkout_pane, $order);
  }
  return $form;
}

/**
 * Payment redirect pane: validate callback.
 */
function commerce_payment_redirect_pane_form_validate($pane_values, $checkout_pane, $order) {
  // Delegate to the payment method.
  $current_payment_method = $order->payment_methods[$order->payment_method];
  $payment_method_info = commerce_payment_method($current_payment_method['method_id']);
  if (function_exists($callback = $payment_method_info['callbacks']['redirect_form_validate'])) {
    return $callback($current_payment_method, $pane_values, $checkout_pane, $order);
  }
  else {
    return TRUE;
  }
}

/**
 * Payment redirect pane: submit callback.
 */
function commerce_payment_redirect_pane_form_submit($pane_values, $checkout_pane, $order) {
  // Simply pass the control to the payment method.
  $current_payment_method = $order->payment_methods[$order->payment_method];
  $payment_method_info = commerce_payment_method($current_payment_method['method_id']);
  if (function_exists($callback = $payment_method_info['callbacks']['redirect_form_submit'])) {
    $form = $callback($current_payment_method, $pane_values, $checkout_pane, $order);
  }
  else {
    return TRUE;
  }
}

/**
 * Utility function: return a payment redirect page for POST.
 *
 * @param $action
 *   The destination URL the values should be posted to.
 * @param $values
 *   An associative array of values that will be posted to the destination URL.
 * @return
 *   A renderable array.
 */
function commerce_payment_post_redirect_form($action, array $values = array()) {
  $form = array(
    '#type' => 'form',
    '#action' => $action,
    '#method' => 'POST',
    '#id' => '',
    '#attributes' => array(),
  );
  foreach ($values as $key => $value) {
    $form[$value] = array(
      '#type' => 'hidden',
      '#name' => $key,
      '#value' => $value,
      '#id' => '',
      '#attributes' => array(),
    );
  }
  $form['submit'] = array(
    '#type' => 'submit',
    '#id' => '',
    '#value' => t('Process to payment'),
  );

  return array(
    'form' => array(
      '#type' => 'markup',
      '#markup' => drupal_render($form),
    ),
  );
}
