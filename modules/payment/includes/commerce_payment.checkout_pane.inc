<?php
// $Id$

/**
 * @file
 * Callback functions for the Payment module's checkout panes.
 */


/**
 * Payment pane: form callback.
 */
function commerce_payment_pane_checkout_form($form, &$form_state, $checkout_pane, $order) {
  $pane_form = array();

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

  // If no payment methods were found, return the empty form.
  if (empty($options)) {
    return $pane_form;
  }

  // Store the payment methods in the form for validation purposes.
  $pane_form['payment_methods'] = array(
    '#type' => 'value',
    '#value' => $order->payment_methods,
  );

  // If at least one payment option is available...
  if (!empty($options)) {
    // Add a radio select widget to specify the payment method.
    $pane_form['payment_method'] = array(
      '#type' => 'radios',
      '#options' => $options,
      '#ajax' => array(
        'callback' => 'commerce_payment_pane_checkout_form_details_refresh',
        'wrapper' => 'payment-details',
      ),
    );

    // Find the default payment method using either the preselected value stored
    // in the order / checkout pane or the first available method.
    $pane_values = !empty($form_state['values']) ? $form_state['values'][$checkout_pane->pane_id] : array();

    if (isset($pane_values['payment_method']) && isset($options[$pane_values['payment_method']])) {
      $default_value = $pane_values['payment_method'];
    }
    elseif (isset($order->data['payment_method']) && isset($options[$order->data['payment_method']])) {
      $default_value = $order->data['payment_method'];
    }
    else {
      $default_value = array_shift(array_keys($options));
    }

    // Set the default value for the payment method radios.
    $pane_form['payment_method']['#default_value'] = $default_value;

    // Add the payment method specific form elements.
    $payment_method = commerce_payment_method_load($pane_form['payment_method']['#default_value']);

    if (function_exists($callback = $payment_method->callbacks['submit_form'])) {
      $pane_form['payment_details'] = $callback($payment_method, $pane_values, $checkout_pane, $order);
    }
    else {
      $pane_form['payment_details'] = array();
    }

    $pane_form['payment_details']['#prefix'] = '<div id="payment-details">';
    $pane_form['payment_details']['#suffix'] = '</div>';
  }

  return $pane_form;
}

/**
 * Returns the payment details element for display via AJAX.
 */
function commerce_payment_pane_checkout_form_details_refresh($form, $form_state) {
  return $form['commerce_payment']['payment_details'];
}

/**
 * Payment pane: validation callback.
 */
function commerce_payment_pane_checkout_form_validate($form, &$form_state, $checkout_pane, $order) {
  $pane_form = $form[$checkout_pane->pane_id];
  $pane_values = $form_state['values'][$checkout_pane->pane_id];

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
  $payment_method = commerce_payment_method_load($pane_values['payment_method']);

  if (function_exists($callback = $payment_method->callbacks['submit_form_validate'])) {
    $result = $callback($payment_method, $pane_form['payment_details'], $pane_values['payment_details'], $order);

    // To prevent payment method validation routines from having to return TRUE
    // explicitly, only return FALSE if it was specifically returned.  Otherwise
    // default to TRUE.
    return $result === FALSE ? FALSE : TRUE;
  }

  // Nothing to validate.
  return TRUE;
}

/**
 * Payment pane: submit callback.
 */
function commerce_payment_pane_checkout_form_submit($form, &$form_state, $checkout_pane, $order) {
  $pane_form = $form[$checkout_pane->pane_id];
  $pane_values = $form_state['values'][$checkout_pane->pane_id];

  $order->data['payment_method'] = $pane_values['payment_method'];

  // Delegate submit to the payment method callback.
  $payment_method = commerce_payment_method_load($pane_values['payment_method']);

  if (function_exists($callback = $payment_method->callbacks['submit_form_submit'])) {
    return $callback($payment_method, $pane_form['payment_details'], $pane_values['payment_details'], $order);
  }
}

/**
 * Payment redirect pane: form callback.
 */
function commerce_payment_redirect_pane_checkout_form(&$form, &$form_state, $checkout_pane, $order) {
  // First load the order's specified payment method.
  $payment_method = commerce_payment_method_load($order->data['payment_method']);

  // If the payment method doesn't exist or does not require a redirect...
  if (!$payment_method || !$payment_method->offsite) {
    // Advance the customer to the next step of the checkout process.
    $next_page = $form['checkout_page']['#value']->next_page;

    $order = commerce_order_status_update($order, 'checkout_' . $next_page);

    drupal_goto('checkout/' . $order->order_id . '/' . $next_page);
  }

  // If the user came to the cancel URL...
  if (arg(3) == 'back' && arg(4) == $order->data['payment_redirect_key']) {
    // Send the customer to the previous step of the checkout process.
    $prev_page = $form['checkout_page']['#value']->prev_page;

    $order = commerce_order_status_update($order, 'checkout_' . $prev_page);

    drupal_goto('checkout/' . $order->order_id . '/' . $prev_page);
  }

  // If the user came to the return URL...
  if (arg(3) == 'return' && arg(4) == $order->data['payment_redirect_key']) {
    // Check for a validate handler on return.
    $callback = $payment_method->callbacks['redirect_form_validate'];

    // If there is no validate handler or if there is and it passess...
    if (!function_exists($callback) || $callback($order, $payment_method)) {
      // Perform any submit functions if necessary.
      if (function_exists($callback = $payment_method->callbacks['redirect_form_submit'])) {
        $callback($order, $payment_method);
      }

      // Send the customer on to the next checkout page.
      $next_page = $form['checkout_page']['#value']->next_page;

      $order = commerce_order_status_update($order, 'checkout_' . $next_page);

      drupal_goto('checkout/' . $order->order_id . '/' . $next_page);
    }
    else {
      // Otherwise display the failure message and send the customer back.
      drupal_set_message(t('Payment failed at the payment server. Please review your information and try again.'), 'error');

      $prev_page = $form['checkout_page']['#value']->prev_page;

      $order = commerce_order_status_update($order, 'checkout_' . $prev_page);

      drupal_goto('checkout/' . $order->order_id . '/' . $prev_page);
    }
  }

  // If the function to build the redirect form exists...
  if (function_exists($callback = $payment_method->callbacks['redirect_form'])) {
    // Generate a key to use in the return URL from the redirected service.
    $order->data['payment_redirect_key'] = drupal_hash_base64(time());
    commerce_order_save($order);

    // TODO: Get the settings for the method from its action settings. This
    // should be happening upstream based on the actual Rule used and the actual
    // action used... perhaps saving $order->payment_methods from above.
    $rule = rules_config_load('commerce_payment_' . $payment_method->method_id);

    foreach ($rule->actions() as $key => $action) {
      if ($action->getElementName() == 'commerce_payment_enable_' . $payment_method->method_id) {
        $payment_method->settings = $action->settings['payment_method']['settings'];
      }
    }

    // Merge the new form into the current form array, preserving the help text
    // if it exists. We also add a wrapper so the form can be easily submitted.
    $form += drupal_get_form($callback, $order, $payment_method);

    $form['#prefix'] = '<div class="payment-redirect-form">';
    $form['#suffix'] = '</div>';
  }
  else {
    // Alert the administrator that the module does not provide a required form.
    drupal_set_message(t('The %title payment method indicates it is offsite but does not define the necessary form to process the redirect.', array('%title' => $payment_method->title)), 'error');
  }
}

/**
 * Payment redirect pane: validate callback.
 */
function commerce_payment_redirect_pane_checkout_form_validate($pane_form, &$form_state, $pane_values, $checkout_pane, $order) {
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
function commerce_payment_redirect_pane_checkout_form_submit($pane_values, $checkout_pane, $order) {
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
    '#value' => t('Proceed to payment'),
  );

  return array(
    'form' => array(
      '#type' => 'markup',
      '#markup' => drupal_render($form),
    ),
  );
}
