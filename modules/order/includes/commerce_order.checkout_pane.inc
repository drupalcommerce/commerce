<?php

/**
 * @file
 * Checkout pane callback functions for the Order module.
 */


/**
 * Account pane: settings form callback.
 */
function commerce_order_account_pane_settings_form($checkout_pane) {
  $form = array();

  $form['commerce_order_account_pane_mail_double_entry'] = array(
    '#type' => 'checkbox',
    '#title' => t('Require double entry of email address.'),
    '#description' => t('Forces anonymous users to enter their email address in two consecutive fields, which must have indentical values.'),
    '#default_value' => variable_get('commerce_order_account_pane_mail_double_entry', FALSE)
  );

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
    $pane_form['login'] = array(
      '#type' => 'container',
      '#prefix' => '<div id="account-login-container">',
      '#suffix' => '</div>',
    );

    $pane_form['login']['mail'] = array(
      '#type' => 'textfield',
      '#title' => t('E-mail address'),
      '#default_value' => $order->mail,
      '#required' => TRUE,
      '#element_validate' => array('commerce_order_mail_validate'),
      '#attributes' => array(
        'autocomplete' => 'email',
      ),
    );

    if (variable_get('commerce_order_account_pane_mail_double_entry', FALSE)) {
      $pane_form['login']['mail_confirm'] = array(
        '#type' => 'textfield',
        '#title' => t('Confirm e-mail address'),
        '#description' => t('Provide your e-mail address in both fields.'),
        '#default_value' => $order->mail,
        '#required' => TRUE,
        '#element_validate' => array('commerce_order_mail_validate'),
      );
    }
  }

  return $pane_form;
}

/**
 * Account pane: validation callback.
 */
function commerce_order_account_pane_checkout_form_validate($form, &$form_state, $checkout_pane, $order) {
  if (!empty($form_state['values'][$checkout_pane['pane_id']])) {
    $pane_values = $form_state['values'][$checkout_pane['pane_id']];

    // If the e-mail address field was present on the form...
    if (!empty($pane_values['login']['mail'])) {
      // Display an error if an invalid e-mail address was given.
      if ($error = user_validate_mail($pane_values['login']['mail'])) {
        form_set_error($checkout_pane['pane_id'] . '][login][mail', $error);
        return FALSE;
      }
      if (variable_get('commerce_order_account_pane_mail_double_entry', FALSE)) {
        // Display an error if the two email addresses don't match.
        if ($pane_values['login']['mail'] != $pane_values['login']['mail_confirm']) {
          form_set_error($checkout_pane['pane_id'] . '][login][mail_confirm', t('The specified e-mail addresses do not match.'));
          return FALSE;
        }
      }
    }
  }

  return TRUE;
}

/**
 * Account pane: checkout form submission callback.
 */
function commerce_order_account_pane_checkout_form_submit($form, &$form_state, $checkout_pane, $order) {
  if (!empty($form_state['values'][$checkout_pane['pane_id']])) {
    $pane_values = $form_state['values'][$checkout_pane['pane_id']];

    if (!empty($pane_values['login']['mail'])) {
      $order->mail = $pane_values['login']['mail'];
    }
  }
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
