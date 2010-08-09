<?php
// $Id$

/**
 * @file
 * Callback functions for the Payment module's checkout panes.
 */


/**
 * Payment pane: form callback.
 */
function commerce_payment_pane_form($pane_values, $checkout_pane, $order) {
  $form = array();

  // Invoke the payment methods event that will populate the order with
  // an array of method IDs for available payment methods.
  $order->payment_methods = array();
  rules_invoke_event('commerce_payment_methods', $order);

  // Generate an array of payment method options for the checkout form.
  $options = array();

  foreach ((array) $order->payment_methods as $method_id) {
    if ($payment_method = commerce_payment_method_load($method_id)) {
      $options[$method_id] = $payment_method->title;
    }
  }

  // Store the payment methods in the form for validation purposes.
  $form['payment_methods'] = array(
    '#type' => 'value',
    '#value' => $order->payment_methods,
  );

  // If at least one payment option is available...
  if (!empty($options)) {
    // Add a radio select widget to specify the payment method.
    $form['payment_method'] = array(
      '#type' => 'radios',
      '#options' => $options,
    );

    // Find the default payment method using either the preselected value stored
    // in the order / checkout pane or the first available method.
    if (isset($order->data['payment_method']) && isset($options[$order->data['payment_method']])) {
      $default_value = $order->data['payment_method'];
    }
    elseif (isset($pane_values['payment_method']) && isset($options[$pane_values['payment_method']])) {
      $default_value = $pane_values['payment_method'];
    }
    else {
      $default_value = array_shift(array_keys($options));
    }

    // Set the default value for the payment method radios.
    $form['payment_method']['#default_value'] = $default_value;

    // Add the payment method specific form elements.
    $payment_method = commerce_payment_method_load($form['payment_method']['#default_value']);

    if (function_exists($callback = $payment_method->callbacks['submit_form'])) {
      $form['payment'] = $callback($payment_method, $pane_values, $checkout_pane, $order);
    }
  }

  return $form;
}

/**
 * Payment pane: validation callback.
 */
function commerce_payment_pane_form_validate($pane_form, $pane_values, $order) {
  // If the selected payment method was changed...
  if ($pane_values['payment_method'] != $pane_form['payment_method']['#default_value']) {
    // And the newly selected method has a valid form callback...
    if ($payment_method = commerce_payment_method_load($pane_values['payment_method'])) {
      if (function_exists($payment_method->callbacks['submit_form'])) {
        // Fail validation so the form is rebuilt to include the payment method
        // specific form elements.
        return FALSE;
      }
    }
  }

  // Delegate validation to the payment method callback.
  $payment_method = commerce_payment_method_load($pane_values['payment_methods']);

  if (function_exists($callback = $payment_method->callbacks['submit_form_validate'])) {
    return $callback($payment_method, $pane_form['payment'], $pane_values['payment'], $order);
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
  $payment_method = commerce_payment_method_load($pane_values['payment_method']);

  if (function_exists($callback = $payment_method->callbacks['submit_form_submit'])) {
    return $callback($payment_method, $pane_form['payment'], $pane_values['payment'], $order);
  }
}

/**
 * Payment redirect pane: form callback.
 */
function commerce_payment_redirect_pane_form($pane_values, $checkout_pane, $order) {
  $form = array();

  // Delegate to the payment method.
  $payment_method = commerce_payment_method_load($order->data['payment_method']);

  if (function_exists($callback = $payment_method->callbacks['redirect_form'])) {
    $form = $callback($payment_method, $pane_values, $checkout_pane, $order);
  }

  return $form;
}

/**
 * Payment redirect pane: validate callback.
 */
function commerce_payment_redirect_pane_form_validate($pane_values, $checkout_pane, $order) {
  // Delegate to the payment method.
  $current_payment_method = $order->payment_methods[$order->payment_method];
  $payment_method = commerce_payment_method_load($current_payment_method['method_id']);

  if (function_exists($callback = $payment_method->callbacks['redirect_form_validate'])) {
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
  $payment_method = commerce_payment_method_load($current_payment_method['method_id']);

  if (function_exists($callback = $payment_method->callbacks['redirect_form_submit'])) {
    $callback($current_payment_method, $pane_values, $checkout_pane, $order);
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
