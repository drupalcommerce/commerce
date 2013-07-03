<?php

/**
 * @file
 * Callback functions for the Payment module's checkout panes.
 */

/**
 * Checkout pane callback: returns the payment pane's settings form.
 */
function commerce_payment_pane_settings_form($checkout_pane) {
  $form = array();

  $form['commerce_payment_pane_require_method'] = array(
    '#type' => 'checkbox',
    '#title' => t('Require a payment method at all times, preventing checkout if none is available.'),
    '#default_value' => variable_get('commerce_payment_pane_require_method', FALSE),
  );

  return $form;
}

/**
 * Payment pane: form callback.
 */
function commerce_payment_pane_checkout_form($form, &$form_state, $checkout_pane, $order) {
  $pane_form = array();

  // Invoke the payment methods event that will populate the order with
  // an array of method IDs for available payment methods.
  $order->payment_methods = array();
  rules_invoke_all('commerce_payment_methods', $order);

  // Sort the payment methods array by the enabling Rules' weight values.
  uasort($order->payment_methods, 'drupal_sort_weight');

  // Generate an array of payment method options for the checkout form.
  $options = array();

  foreach ($order->payment_methods as $instance_id => $method_info) {
    // Ensure we've received a valid payment method that can be used on the
    // checkout form.
    if ($payment_method = commerce_payment_method_load($method_info['method_id'])) {
      if (!empty($payment_method['checkout'])) {
        $options[$instance_id] = $payment_method['display_title'];
      }
    }
  }

  // If no payment methods were found, return the empty form.
  if (empty($options)) {
    if (!variable_get('commerce_payment_pane_require_method', FALSE)) {
      return $pane_form;
    }
    else {
      $pane_form['message'] = array(
        '#markup' => '<div>' . t('Unfortunately we could not find any suitable payment methods, and we require a payment method to complete checkout.') . '<br /><strong>' . t('Please contact us to resolve any issues with your order.') . '</strong></div>',
      );
    }
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
    $pane_values = !empty($form_state['values'][$checkout_pane['pane_id']]) ? $form_state['values'][$checkout_pane['pane_id']] : array();

    if (isset($pane_values['payment_method']) && isset($options[$pane_values['payment_method']])) {
      $default_value = $pane_values['payment_method'];
    }
    elseif (isset($form_state['input']['commerce_payment']['payment_method'])) {
      $default_value = $form_state['complete form']['commerce_payment']['payment_method']['#default_value'];
    }
    elseif (isset($order->data['payment_method']) && isset($options[$order->data['payment_method']])) {
      $default_value = $order->data['payment_method'];
    }
    else {
      reset($options);
      $default_value = key($options);
    }

    // Set the default value for the payment method radios.
    $pane_form['payment_method']['#default_value'] = $default_value;

    // Add the payment method specific form elements.
    $method_info = $order->payment_methods[$pane_form['payment_method']['#default_value']];
    $payment_method = commerce_payment_method_load($method_info['method_id']);
    $payment_method['settings'] = $method_info['settings'];

    if ($callback = commerce_payment_method_callback($payment_method, 'submit_form')) {
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
  $pane_id = $checkout_pane['pane_id'];

  // Only attempt validation if we actually had payment methods on the form.
  if (!empty($form[$pane_id]) && !empty($form_state['values'][$pane_id])) {
    $pane_form = $form[$pane_id];
    $pane_values = $form_state['values'][$pane_id];

    // Only attempt validation if there were payment methods available.
    if (!empty($pane_values['payment_methods'])) {
      // If the selected payment method was changed...
      if ($pane_values['payment_method'] != $pane_form['payment_method']['#default_value']) {
        // And the newly selected method has a valid form callback...
        if ($payment_method = commerce_payment_method_instance_load($pane_values['payment_method'])) {
          if (commerce_payment_method_callback($payment_method, 'submit_form')) {
            // Fail validation so the form is rebuilt to include the payment method
            // specific form elements.
            return FALSE;
          }
        }
      }

      // Delegate validation to the payment method callback.
      $payment_method = commerce_payment_method_instance_load($pane_values['payment_method']);

      if ($callback = commerce_payment_method_callback($payment_method, 'submit_form_validate')) {
        // Initialize the payment details array to accommodate payment methods
        // that don't add any additional details to the checkout pane form.
        if (!isset($pane_values['payment_details'])) {
          $pane_values['payment_details'] = array();
        }

        $result = $callback($payment_method, $pane_form['payment_details'], $pane_values['payment_details'], $order, array($checkout_pane['pane_id'], 'payment_details'));

        // To prevent payment method validation routines from having to return TRUE
        // explicitly, only return FALSE if it was specifically returned.  Otherwise
        // default to TRUE.
        return $result === FALSE ? FALSE : TRUE;
      }
    }
    elseif (variable_get('commerce_payment_pane_require_method', FALSE)) {
      drupal_set_message(t('You cannot complete checkout without submitting payment. Please contact us if an error continues to prevent you from seeing valid payment methods for your order.'), 'error');
      return FALSE;
    }
  }
  else {
    // Otherwise ensure we don't have any leftover payment method data in the
    // order's data array.
    unset($order->data['payment_method'], $order->data['payment_redirect_key']);
  }

  // Nothing to validate.
  return TRUE;
}

/**
 * Payment pane: submit callback.
 */
function commerce_payment_pane_checkout_form_submit($form, &$form_state, $checkout_pane, $order) {
  // Check to make sure there are no validation issues with other form elements
  // before executing payment method callbacks.
  if (form_get_errors()) {
    drupal_set_message(t('Your payment will not be processed until all errors on the page have been addressed.'), 'warning');
    return FALSE;
  }

  $pane_id = $checkout_pane['pane_id'];

  // Only submit if we actually had payment methods on the form.
  if (!empty($form[$pane_id]) && !empty($form_state['values'][$pane_id])) {
    $pane_form = $form[$pane_id];
    $pane_values = $form_state['values'][$pane_id];

    // Only process if there were payment methods available.
    if ($pane_values['payment_methods']) {
      $order->data['payment_method'] = $pane_values['payment_method'];

      // If we can calculate a single order total for the order...
      if ($balance = commerce_payment_order_balance($order)) {
        // Delegate submit to the payment method callback.
        $payment_method = commerce_payment_method_instance_load($pane_values['payment_method']);

        if ($callback = commerce_payment_method_callback($payment_method, 'submit_form_submit')) {
          // Initialize the payment details array to accommodate payment methods
          // that don't add any additional details to the checkout pane form.
          if (empty($pane_values['payment_details'])) {
            $pane_values['payment_details'] = array();
          }

          // If payment fails, rebuild the checkout form without progressing.
          if ($callback($payment_method, $pane_form['payment_details'], $pane_values['payment_details'], $order, $balance) === FALSE) {
            $form_state['rebuild'] = TRUE;
          }
        }
      }
    }
  }
}

/**
 * Payment redirect pane: form callback.
 */
function commerce_payment_redirect_pane_checkout_form(&$form, &$form_state, $checkout_pane, $order) {
  // First load the order's specified payment method instance.
  if (!empty($order->data['payment_method'])) {
    $payment_method = commerce_payment_method_instance_load($order->data['payment_method']);
  }
  else {
    $payment_method = FALSE;
  }

  // If the payment method doesn't exist or does not require a redirect...
  if (!$payment_method || !$payment_method['offsite']) {
    if (!$payment_method) {
      $log = t('Customer skipped the Payment page because no payment was required.');
    }
    else {
      $log = t('Customer skipped the Payment page because payment was already submitted.');
    }

    // Advance the customer to the next step of the checkout process.
    commerce_payment_redirect_pane_next_page($order, $log);
    drupal_goto(commerce_checkout_order_uri($order));
  }

  // If the user came to the cancel URL...
  if (arg(3) == 'back' && arg(4) == $order->data['payment_redirect_key']) {
    // Perform any payment cancellation functions if necessary.
    if ($callback = commerce_payment_method_callback($payment_method, 'redirect_form_back')) {
      $callback($order, $payment_method);
    }

    // Send the customer to the previous step of the checkout process.
    commerce_payment_redirect_pane_previous_page($order, t('Customer canceled payment at the payment gateway.'));
    drupal_goto(commerce_checkout_order_uri($order));
  }

  // If the user came to the return URL...
  if (arg(3) == 'return' && arg(4) == $order->data['payment_redirect_key']) {
    // Check for a validate handler on return.
    $validate_callback = commerce_payment_method_callback($payment_method, 'redirect_form_validate');

    // If there is no validate handler or if there is and it isn't FALSE...
    if (!$validate_callback || $validate_callback($order, $payment_method) !== FALSE) {
      // Perform any submit functions if necessary.
      if ($callback = commerce_payment_method_callback($payment_method, 'redirect_form_submit')) {
        $callback($order, $payment_method);
      }

      // Send the customer on to the next checkout page.
      commerce_payment_redirect_pane_next_page($order, t('Customer successfully submitted payment at the payment gateway.'));
      drupal_goto(commerce_checkout_order_uri($order));
    }
    else {
      // Otherwise display the failure message and send the customer back.
      drupal_set_message(t('Payment failed at the payment server. Please review your information and try again.'), 'error');

      commerce_payment_redirect_pane_previous_page($order, t('Customer payment submission failed at the payment gateway.'));
      drupal_goto(commerce_checkout_order_uri($order));
    }
  }

  // If the function to build the redirect form exists...
  if ($callback = commerce_payment_method_callback($payment_method, 'redirect_form')) {
    // Generate a key to use in the return URL from the redirected service if it
    // does not already exist.
    if (empty($order->data['payment_redirect_key'])) {
      $order->data['payment_redirect_key'] = drupal_hash_base64(time());
      commerce_order_save($order);
    }

    // If the payment method has the 'offsite_autoredirect' option enabled, add
    // the redirection behavior.
    if (!empty($payment_method['offsite_autoredirect'])) {
      $form['#attached']['js'][] = drupal_get_path('module', 'commerce_payment') . '/commerce_payment.js';
      $form['help']['#markup'] = '<div class="checkout-help">' . t('Please wait while you are redirected to the payment server. If nothing happens within 10 seconds, please click on the button below.') . '</div>';
    }

    // Merge the new form into the current form array, preserving the help text
    // if it exists. We also add a wrapper so the form can be easily submitted.
    $form += drupal_get_form($callback, $order, $payment_method);

    $form['#prefix'] = '<div class="payment-redirect-form">';
    $form['#suffix'] = '</div>';
  }
  else {
    // Alert the administrator that the module does not provide a required form.
    drupal_set_message(t('The %title payment method indicates it is offsite but does not define the necessary form to process the redirect.', array('%title' => $payment_method['title'])), 'error');
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
