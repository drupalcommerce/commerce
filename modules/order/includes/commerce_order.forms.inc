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
  $form = array();

  // Ensure this include file is loaded when the form is rebuilt from the cache.
  $form_state['build_info']['file'] = drupal_get_path('module', 'commerce_order') . '/includes/commerce_order.forms.inc';

  // Add the field related form elements.
  $form_state['commerce_order'] = $order;
  field_attach_form('commerce_order', $order, $form, $form_state);

  // Add the default field elements.
  $form['customer'] = array(
    '#type' => 'fieldset',
    '#title' => t('Customer'),
  );
  $form['customer']['customer_uid'] = array(
    '#type' => 'textfield',
    '#title' => t('Customer uid'),
    '#default_value' => $order->customer_uid,
  );

  $form['creator'] = array(
    '#type' => 'fieldset',
    '#title' => t('Creator'),
  );
  $form['creator']['creator_uid'] = array(
    '#type' => 'textfield',
    '#title' => t('Creator uid'),
    '#default_value' => $order->creator_uid,
  );

  $form['actions'] = array(
    '#type' => 'container',
    '#attributes' => array('class' => array('form-actions')),
    '#weight' => 100,
  );
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save order'),
    '#weight' => 40,
  );

  return $form;
}

/**
 * Validation callback for commerce_order_order_form().
 */
function commerce_order_order_form_validate($form, &$form_state) {
  $order = $form_state['commerce_order'];

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

  $order->customer_uid = $form_state['values']['customer_uid'];

  // Set the order's creator uid if it's being created at this time.
  if (empty($order->order_id) && empty($form_state['values']['creator_uid'])) {
    $order->creator_uid = $user->uid;
  }
  else {
    $order->creator_uid = $form_state['values']['creator_uid'];
  }

  // Notify field widgets.
  field_attach_submit('commerce_order', $order, $form, $form_state);

  // Save the order.
  $order = commerce_order_save($order);

  // Apply the redirect based on the clicked button.
  if ($form_state['clicked_button']['#value'] == t('Save order')) {
    drupal_set_message(t('Order saved.'));

    $form_state['redirect'] = 'admin/commerce/orders/' . $order->order_id . '/edit';
  }
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

  $form = confirm_form($form,
    t('Are you sure you want to delete order @number?', array('@number' => $order->order_number)),
    'admin/commerce/orders',
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

  $form_state['redirect'] = 'admin/commerce/orders';
}
