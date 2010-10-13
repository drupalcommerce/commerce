<?php
// $Id$

/**
 * @file
 * Forms for creating / editing and deleting orders.
 */


/**
 * Form callback: create or edit an order.
 *
 * @param $order
 *   The order object to edit through the form.
 */
function commerce_order_order_form($form, &$form_state, $order) {
  // Ensure this include file is loaded when the form is rebuilt from the cache.
  $form_state['build_info']['files']['form'] = drupal_get_path('module', 'commerce_order') . '/includes/commerce_order.forms.inc';

  // Ensure the creator name is accessible if the uid is set.
  if (!empty($order->uid) && $creator = user_load($order->uid)) {
    $order->name = $creator->name;
  }

  if (empty($order->created)) {
    $order->date = format_date(REQUEST_TIME, 'custom', 'Y-m-d H:i:s O');
  }

  // Add the field related form elements.
  $form_state['commerce_order'] = $order;
  field_attach_form('commerce_order', $order, $form, $form_state);

  // Add the default field elements.
  $form['creation'] = array(
    '#type' => 'fieldset',
    '#title' => t('Creation information'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#access' => user_access('administer orders'),
    '#group' => 'additional_settings',
    '#weight' => 90,
  );
  $form['creation']['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Created by'),
    '#maxlength' => 60,
    '#autocomplete_path' => 'user/autocomplete',
    '#default_value' => !empty($order->name) ? $order->name : '',
    '#weight' => -1,
    '#description' => t('Leave blank to set yourself as the creator.'),
  );
  $form['creation']['date'] = array(
    '#type' => 'textfield',
    '#title' => t('Created on'),
    '#maxlength' => 25,
    '#description' => t('Format: %time. The date format is YYYY-MM-DD and %timezone is the time zone offset from UTC. Leave blank to use the time of form submission.', array('%time' => !empty($order->date) ? date_format(date_create($order->date), 'Y-m-d H:i:s O') : format_date($order->created, 'custom', 'Y-m-d H:i:s O'), '%timezone' => !empty($order->date) ? date_format(date_create($order->date), 'O') : format_date($order->created, 'custom', 'O'))),
    '#default_value' => !empty($order->created) ? format_date($order->created, 'custom', 'Y-m-d H:i:s O') : '',
  );

  $form['actions'] = array(
    '#type' => 'container',
    '#attributes' => array('class' => array('form-actions')),
    '#weight' => 100,
  );

  // We add the form's #submit array to this button along with the actual submit
  // handler to preserve any submit handlers added by a form callback_wrapper.
  $submit = array();

  if (!empty($form['#submit'])) {
    $submit += $form['#submit'];
  }

  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save order'),
    '#submit' => $submit + array('commerce_order_order_form_submit'),
    '#weight' => 40,
  );

  // We append the validate handler to #validate in case a form callback_wrapper
  // is used to add validate handlers earlier.
  $form['#validate'][] = 'commerce_order_order_form_validate';

  return $form;
}

/**
 * Validation callback for commerce_order_order_form().
 */
function commerce_order_order_form_validate($form, &$form_state) {
  $order = $form_state['commerce_order'];

  // Validate the "created by" field.
  if (!empty($form_state['values']['name']) && !($account = user_load_by_name($form_state['values']['name']))) {
    // The use of empty() is mandatory in the context of usernames
    // as the empty string denotes the current user.
    form_set_error('name', t('The username %name does not exist.', array('%name' => $form_state['values']['name'])));
  }

  // Validate the "created on" field.
  if (!empty($form_state['values']['date']) && strtotime($form_state['values']['date']) === FALSE) {
    form_set_error('date', t('You have to specify a valid date.'));
  }

  // TODO: Pending token patterns for order numbers, validate the characters and
  // the final string for uniqueness.

  // Notify field widgets to validate their data.
  field_attach_form_validate('commerce_order', $order, $form, $form_state);
}

/**
 * Submit callback for commerce_order_order_form().
 */
function commerce_order_order_form_submit($form, &$form_state) {
  global $user;

  $order = &$form_state['commerce_order'];

  // Set the order's creator uid if it's being created at this time.
  if (empty($order->order_id) && empty($form_state['values']['name'])) {
    $order->uid = $user->uid;
  }
  else {
    if ($account = user_load_by_name($form_state['values']['name'])) {
      $order->uid = $account->uid;
    }
    else {
      $order->uid = 0;
    }
  }

  $order->created = !empty($form_state['values']['date']) ? strtotime($form_state['values']['date']) : REQUEST_TIME;

  // Notify field widgets.
  field_attach_submit('commerce_order', $order, $form, $form_state);

  // Save the order.
  $order = commerce_order_save($order);
}

/**
 * Form callback: confirmation form for deleting an order.
 *
 * @param $order
 *   The order object to delete through the form.
 *
 * @return
 *   The form array to add or edit an order.
 *
 * @see confirm_form()
 */
function commerce_order_order_delete_form($form, &$form_state, $order) {
  $form_state['order'] = $order;

  // Ensure this include file is loaded when the form is rebuilt from the cache.
  $form_state['build_info']['files']['form'] = drupal_get_path('module', 'commerce_order') . '/includes/commerce_order.forms.inc';

  $form['#submit'][] = 'commerce_order_order_delete_form_submit';

  $form = confirm_form($form,
    t('Are you sure you want to delete order @number?', array('@number' => $order->order_number)),
    '',
    '<p>' . t('Deleting this order cannot be undone.') . '</p>',
    t('Delete'),
    t('Cancel'),
    'confirm'
  );

  return $form;
}

/**
 * Submit callback for commerce_order_order_delete_form().
 */
function commerce_order_order_delete_form_submit($form, &$form_state) {
  $order = $form_state['order'];

  if (commerce_order_delete($order->order_id)) {
    drupal_set_message(t('Order @number has been deleted.', array('@number' => $order->order_number)));
    watchdog('commerce_order', 'Deleted order @number.', array('@number' => $order->order_number), WATCHDOG_NOTICE);
  }
  else {
    drupal_set_message(t('Order @number could not be deleted.', array('@number' => $order-->order_number)), 'error');
  }
}
