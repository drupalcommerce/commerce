<?php
// $Id$


/**
 * Form callback: edit the global order settings.
 */
function commerce_order_settings_form() {
  $form = array();

  $form['commerce_order_help_text'] = array(
    '#type' => 'textarea',
    '#title' => t('Order creation help text'),
    '#description' => t('Supply an optional help message to be displayed above the order add form.'),
    '#default_value' => variable_get('commerce_order_help_text', ''),
  );

  return system_settings_form($form);
}

/**
 * Display a view of an order entity.
 */
function commerce_order_ui_order_view($mode, $order) {
  return '<pre>' . print_r($order, TRUE) . '</pre>';
}

/**
 * Form callback: create or edit an order.
 */
function commerce_order_ui_order_form($form, &$form_state, $order, $account = NULL) {
  $form = array();

  // Add the default field elements.

  // Add the field related form elements.
  $form_state['commerce_order'] = $order;
  field_attach_form('commerce_order', $order, $form, $form_state);

  $form['actions'] = array('#type' => 'container', '#attributes' => array('class' => array('form-actions')));
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save order'),
    '#suffix' => l('Cancel', 'admin/commerce/orders'),
    '#weight' => 40,
  );

  return $form;
}

/**
 * Validation callback for commerce_order_ui_form().
 */
function commerce_order_ui_order_form_validate($form, &$form_state) {
  $order = $form_state['commerce_order'];

  // TODO: Pending token patterns for order numbers, validate the characters and
  // the final string for uniqueness.

  // Notify field widgets to validate their data.
  field_attach_form_validate('commerce_order', $order, $form, $form_state);
}

/**
 * Submit callback for commerce_order_ui_form().
 */
function commerce_order_ui_order_form_submit($form, &$form_state) {
  global $user;

  $order = &$form_state['commerce_order'];

  // Set the order's creator uid if it's being created at this time.
  if (empty($order->order_id)) {
    $order->creator_uid = $user->uid;
  }

  // Notify field widgets.
  field_attach_submit('commerce_order', $order, $form, $form_state);

  // Save the order.
  $order = commerce_order_save($order);

  drupal_set_message(t('Order saved.'));

  // TODO: Determine alternate redirect location if not the edit page.
  $form_state['redirect'] = 'admin/commerce/orders/' . $order->order_id . '/edit';
}

/**
 * Confirmation form for deleting an order.
 */
function commerce_order_ui_order_delete_form($form, &$form_state, $order) {
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
 * Submit callback for commerce_order_ui_order_delete_form().
 */
function commerce_order_ui_order_delete_form_submit($form, &$form_state) {
  $order = $form_state['order'];

  if (commerce_order_delete($order->order_id)) {
    drupal_set_message(t('Order @number has been deleted.', array('@number' => $order->order_number)));
    watchdog('commerce_order', 'Deleted order @number.', array('@number' => $order->order_number), WATCHDOG_NOTICE);
  }

  $form_state['redirect'] = 'admin/commerce/orders';
}
