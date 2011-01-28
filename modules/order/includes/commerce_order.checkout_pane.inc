<?php
// $Id$

/**
 * @file
 * Checkout pane callback functions for the Order module.
 */


/**
 * Account pane: settings form callback.
 */
function commerce_order_account_pane_settings_form($checkout_pane) {
  $form = array();

  $form['commerce_order_account_pane_auth_display'] = array(
    '#type' => 'checkbox',
    '#title' => t('Display the account information pane for authenticated users.'),
    '#description' => t('If checked, the pane will show account information to authenticated users but not allow changes.'),
    '#default_value' => variable_get('commerce_order_account_pane_auth_display', FALSE)
  );

  return $form;
}

/**
 * Account pane: form callback.
 */
function commerce_order_account_pane_checkout_form($form, &$form_state, $checkout_pane, $order) {
  global $user;

  $pane_form = array();

  // If the user is logged in...
  if ($user->uid > 0) {
    // And the pane has been configured to display account information...
    if (variable_get('commerce_order_account_pane_auth_display', FALSE)) {
      // Note we're not using theme_username() to avoid linking out of checkout.
      $pane_form['username'] = array(
        '#type' => 'item',
        '#title' => t('Username'),
        '#markup' => check_plain($user->name),
      );
      $pane_form['mail'] = array(
        '#type' => 'item',
        '#title' => t('E-mail address'),
        '#markup' => check_plain($order->mail),
      );
    }
  }
  else {
    // Otherwise add an order e-mail address field to the form.
    $pane_form['mail'] = array(
      '#type' => 'textfield',
      '#title' => t('E-mail address'),
      '#default_value' => $order->mail,
      '#required' => TRUE,
      '#ajax' => array(
        'callback' => 'commerce_order_account_pane_checkout_form_refresh',
        'wrapper' => 'account-information-pane',
      ),
      '#prefix' => '<div id="account-information-pane">',
      '#suffix' => '</div>',
    );

    // Check the form state to see if an e-mail address has been specified.
    if (!empty($form_state['values'][$checkout_pane->pane_id])) {
      $mail = trim($form_state['values'][$checkout_pane->pane_id]['mail']);

      // Don't attempt to load the user for an invalid e-mail address.
      if ($error = user_validate_mail($mail)) {
        form_set_error($checkout_pane->pane_id . '][mail', $error);
      }
      elseif (user_load_by_mail($mail)) {
        // If a user already exists for the given e-mail address, display a
        // message letting the customer know this.
        $pane_form['mail']['#description'] = t('There is already an account registered to %mail. <a href="!login_url">Login now</a> to use your account information during checkout.', array('%mail' => $mail, '!login_url' => url('user/login')));
      }
    }
  }

  return $pane_form;
}

/**
 * Account pane: validatation callback.
 */
function commerce_order_account_pane_checkout_form_validate($form, &$form_state, $checkout_pane, $order) {
  $pane_values = $form_state['values'][$checkout_pane->pane_id];

  // If the e-mail address field was present on the form...
  if (!empty($pane_values['mail'])) {
    // Display an error if an invalid e-mail address was given.
    if ($error = user_validate_mail($pane_values['mail'])) {
      form_set_error($checkout_pane->pane_id . '][mail', $error);
      return FALSE;
    }
  }

  return TRUE;
}

/**
 * Account pane: checkout form submission callback.
 */
function commerce_order_account_pane_checkout_form_submit($form, &$form_state, $checkout_pane, $order) {
  $pane_values = $form_state['values'][$checkout_pane->pane_id];

  if (!empty($pane_values['mail'])) {
    $order->mail = $pane_values['mail'];
  }
}

/**
 * Ajax callback: returns the account information pane to an AJAX request.
 */
function commerce_order_account_pane_checkout_form_refresh($form, $form_state) {
  return $form['account']['mail'];
}

/**
 * Account pane: returns the username and e-mail for the Review checkout pane.
 */
function commerce_order_account_pane_review($form, $form_state, $checkout_pane, $order) {
  $content = array();

  // Display the username if it's different from the e-mail address.
  if ($order->uid > 0) {
    $account = user_load($order->uid);

    if ($account->name != $order->mail) {
      // Note we're not using theme_username() to avoid linking out of checkout.
      $content[] = array(
        '#type' => 'item',
        '#title' => t('Username'),
        '#markup' => check_plain($account->name),
      );
    }
  }

  $content[] = array(
    '#type' => 'item',
    '#title' => t('E-mail address'),
    '#markup' => check_plain($order->mail),
  );

  return drupal_render($content);
}
