<?php
// $Id$

/**
 * @file
 * Checkout pane callback functions for the order module.
 */


/**
 * Checkout pane callback: returns the order mail settings form.
 */
function commerce_order_mail_pane_settings_form($checkout_pane) {
  $form = array();
  
  $form['commerce_' . $checkout_pane->pane_id . '_display_auth'] = array(
    '#type' => 'checkbox',
    '#title' => t('Display for authenticated'),
    '#description' => t('Check this box if you want this pane to be displayed for authenticated users. The pane will show the username and emailadres, but won\'t allow the user to changes these parameters'),
    '#default_value' => variable_get('commerce_' . $checkout_pane->pane_id . '_display_auth', FALSE)
  );
  
  return $form;
}

/**
 * Checkout pane callback: returns a order mail form.
 */
function commerce_order_mail_pane_checkout_form($form, &$form_state, $checkout_pane, $order) {
  global $user;

  $pane_form = array();
  
  if ($user->uid > 0) {
    if (variable_get('commerce_' . $checkout_pane->pane_id . '_display_auth', FALSE)) {
      $pane_form['username'] = array(
        '#type' => 'item',
        '#title' => t('Username'),
        '#markup' => theme('username', array(
          'account' => $user
        )),
      );
      $pane_form['mail'] = array(
        '#type' => 'item',
        '#title' => t('E-mail address'),
        '#markup' => check_plain($order->mail),
      );
    }
  } else {
    $pane_form['order_mail'] = array(
      '#type' => 'textfield',
      '#title' => t('E-mail address'),
      '#default_value' => isset($order->mail) ? $order->mail : '',
      '#ajax' => array(
        'callback' => 'commerce_order_mail_pane_checkout_form_ajax',
        'wrapper' => 'order-mail-info',
      ),
      '#required' => TRUE
    );

    $pane_form['order_mail_info'] = commerce_order_mail_pane_checkout_form_order_mail_info($checkout_pane, $form_state);
  }
  
  return $pane_form;
}

/**
 * Checkout pane callback: returns a order mail form (mail info part)
 */
function commerce_order_mail_pane_checkout_form_order_mail_info($checkout_pane, &$form_state) {
  $form_values = isset($form_state['values'][$checkout_pane->pane_id]) ? $form_state['values'][$checkout_pane->pane_id] : array();
  
  $content = '';
  if (isset($form_values['order_mail'])) {
    if ($error = user_validate_mail($form_values['order_mail'])) {
      form_set_error($checkout_pane->pane_id . '][order_mail', $error);
    }
    elseif ((bool) db_select('users')->fields('users', array('uid'))->condition('mail', db_like($form_values['order_mail']), 'LIKE')->range(0, 1)->execute()->fetchField()) {
      $content = t('There is already an account with the emailadres %mail. If you want to login first, you do so <a href="!login_url">here</a>.', array(
        '%mail' => trim($form_values['order_mail']),
        '!login_url' => url('user/login')
      ));
    }
  }
  return array(
    '#markup' => $content,
    '#prefix' => '<div id="order-mail-info">',
    '#suffix' => '</div>'
  );
}

/**
 * Checkout pane callback: validates the order mail form.
 */
function commerce_order_mail_pane_checkout_form_validate($form, &$form_state, $checkout_pane, $order) {
  if ($error = user_validate_mail($form_state['values'][$checkout_pane->pane_id]['order_mail'])) {
    form_set_error($checkout_pane->pane_id . '][order_mail', $error);
    return FALSE;
  }
  
  return TRUE;
}

/**
 * Checkout pane callback: submits the order mail form.
 */
function commerce_order_mail_pane_checkout_form_submit($form, &$form_state, $checkout_pane, $order) {
  $order->mail = $form_state['values'][$checkout_pane->pane_id]['order_mail'];
}

/**
 * Ajax callback: returns the order mail info part.
 */
function commerce_order_mail_pane_checkout_form_ajax($form, $form_state) {
  return $form['order_mail']['order_mail_info'];
}

/**
 * Checkout pane callback: returns the cart contents review data for the
 *   Review checkout pane.
 */
function commerce_order_mail_pane_review($form, $form_state, $checkout_pane, $order) {
  $content = array(
    '#type' => 'item',
    '#title' => t('E-mail address'),
    '#markup' => check_plain($order->mail),
  );
  
  return drupal_render($content);
}
