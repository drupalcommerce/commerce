<?php

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

  // Ensure the owner name is accessible if the uid is set.
  if (!empty($order->uid) && $owner = user_load($order->uid)) {
    $order->name = $owner->name;

    if (empty($order->mail)) {
      $order->mail = $owner->mail;
    }
  }

  if (empty($order->created)) {
    $order->date = format_date(REQUEST_TIME, 'custom', 'Y-m-d H:i:s O');
  }

  // Add the field related form elements.
  $form_state['commerce_order'] = $order;
  field_attach_form('commerce_order', $order, $form, $form_state);

  // Hide the order total field from direct editing.
  $form['commerce_order_total']['#access'] = FALSE;

  $form['additional_settings'] = array(
    '#type' => 'vertical_tabs',
    '#weight' => 99,
  );

  // Build an array of order status options grouped by order state.
  $options = array();

  foreach (commerce_order_state_get_title() as $name => $title) {
    foreach (commerce_order_statuses(array('state' => $name)) as $order_status) {
      $options[check_plain($title)][$order_status['name']] = check_plain($order_status['title']);
    }
  }

  // Add a section to update the status and leave a log message.
  $form['order_status'] = array(
    '#type' => 'fieldset',
    '#title' => t('Order status'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#group' => 'additional_settings',
    '#attached' => array(
      'js' => array(
        drupal_get_path('module', 'commerce_order') . '/commerce_order.js',
        array(
          'type' => 'setting',
          'data' => array('status_titles' => commerce_order_status_get_title()),
        ),
      ),
    ),
    '#weight' => 20,
  );
  $form['order_status']['status'] = array(
    '#type' => 'select',
    '#title' => t('Status'),
    '#options' => $options,
    '#default_value' => $order->status,
  );
  $form['order_status']['status_original'] = array(
    '#type' => 'hidden',
    '#value' => $order->status,
    '#attributes' => array('id' => 'edit-status-original'),
  );
  $form['order_status']['log'] = array(
    '#type' => 'textarea',
    '#title' => t('Update log message'),
    '#description' => t('Provide an explanation of the changes you are making. This will provide a meaningful audit trail for updates to this order.'),
    '#rows' => 4,
  );

  // Add the user account and e-mail fields.
  $form['user'] = array(
    '#type' => 'fieldset',
    '#title' => t('User information'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#access' => user_access('administer commerce_order entities'),
    '#group' => 'additional_settings',
    '#attached' => array(
      'js' => array(
        drupal_get_path('module', 'commerce_order') . '/commerce_order.js',
        array(
          'type' => 'setting',
          'data' => array('anonymous' => variable_get('anonymous', t('Anonymous'))),
        ),
      ),
    ),
    '#weight' => 30,
  );
  $form['user']['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Owned by'),
    '#description' => t('Leave blank for %anonymous.', array('%anonymous' => variable_get('anonymous', t('Anonymous')))),
    '#maxlength' => 60,
    '#autocomplete_path' => 'user/autocomplete',
    '#default_value' => !empty($order->name) ? $order->name : '',
    '#weight' => -1,
  );
  $form['user']['mail'] = array(
    '#type' => 'textfield',
    '#title' => t('Order contact e-mail'),
    '#description' => t('Defaults to the owner account e-mail address if left blank. Used for order communication.'),
    '#default_value' => $order->mail,
  );

  // Add a log checkbox and timestamp field to a history tab.
  $form['order_history'] = array(
    '#type' => 'fieldset',
    '#title' => t('Order history', array(), array('context' => 'a drupal commerce order')),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#group' => 'additional_settings',
    '#attached' => array(
      'js' => array(drupal_get_path('module', 'commerce_order') . '/commerce_order.js'),
    ),
    '#weight' => 40,
  );
  $form['order_history']['revision'] = array(
    '#type' => 'checkbox',
    '#title' => t('Create new revision on update'),
    '#description' => t('If an update log message is entered, a revision will be created even if this is unchecked.'),
    '#default_value' => variable_get('commerce_order_auto_revision', TRUE),
    '#access' => user_access('administer commerce_order entities'),
  );
  $form['order_history']['date'] = array(
    '#type' => 'textfield',
    '#title' => t('Created on'),
    '#description' => t('Format: %time. The date format is YYYY-MM-DD and %timezone is the time zone offset from UTC. Leave blank to use the time of form submission.', array('%time' => !empty($order->date) ? date_format(date_create($order->date), 'Y-m-d H:i:s O') : format_date($order->created, 'custom', 'Y-m-d H:i:s O'), '%timezone' => !empty($order->date) ? date_format(date_create($order->date), 'O') : format_date($order->created, 'custom', 'O'))),
    '#maxlength' => 25,
    '#default_value' => !empty($order->created) ? format_date($order->created, 'custom', 'Y-m-d H:i:s O') : '',
  );
  $form['order_history']['created'] = array(
    '#type' => 'hidden',
    '#value' => !empty($order->created) ? format_date($order->created, 'short') : '',
    '#attributes' => array('id' => 'edit-created'),
  );
  $form['order_history']['changed'] = array(
    '#type' => 'hidden',
    '#value' => !empty($order->changed) ? format_date($order->changed, 'short') : '',
    '#attributes' => array('id' => 'edit-changed'),
  );

  // We add the form's #submit array to this button along with the actual submit
  // handler to preserve any submit handlers added by a form callback_wrapper.
  $submit = array();

  if (!empty($form['#submit'])) {
    $submit += $form['#submit'];
  }

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save order', array(), array('context' => 'a drupal commerce order')),
    '#submit' => array_merge($submit, array('commerce_order_order_form_submit')),
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

  // Validate the "owned by" field.
  if (!empty($form_state['values']['name']) && !($account = user_load_by_name($form_state['values']['name']))) {
    // The use of empty() is mandatory in the context of usernames as the empty
    // string denotes an anonymous user.
    form_set_error('name', t('The username %name does not exist.', array('%name' => $form_state['values']['name'])));
  }

  // Validate the "created on" field.
  if (!empty($form_state['values']['date']) && strtotime($form_state['values']['date']) === FALSE) {
    form_set_error('date', t('You have to specify a valid date.'));
  }

  // Validate the e-mail address entered.
  if (!empty($form_state['values']['mail']) && !valid_email_address($form_state['values']['mail'])) {
    form_set_error('mail', t('You have specified an invalid e-mail address.'));
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

  // If the user is editing an order, load a fresh copy to merge changes to.
  if ($form_state['commerce_order']->order_id) {
    $form_state['commerce_order'] = commerce_order_load($form_state['commerce_order']->order_id);
  }

  // Merge changes into the order object in the form state so it is accessible
  // by field handlers.
  $order = $form_state['commerce_order'];

  if ($form_state['values']['revision'] || !empty($form_state['values']['log'])) {
    $order->revision = TRUE;
    $order->log = $form_state['values']['log'];
  }

  // Set the order's owner uid based on the supplied name.
  $converted = FALSE;

  if (!empty($form_state['values']['name']) && $account = user_load_by_name($form_state['values']['name'])) {
    // If the order is being converted to an authenticated order from an
    // anonymous order...
    if ($order->uid == 0) {
      // Set the converted boolean for later processing.
      $converted = TRUE;
    }

    $order->uid = $account->uid;

    if (empty($form_state['values']['mail'])) {
      $order->mail = $account->mail;
    }
  }
  else {
    $order->uid = 0;
  }

  if (!empty($form_state['values']['mail'])) {
    $order->mail = $form_state['values']['mail'];
  }

  $order->created = !empty($form_state['values']['date']) ? strtotime($form_state['values']['date']) : REQUEST_TIME;

  // Notify field widgets.
  field_attach_submit('commerce_order', $order, $form, $form_state);

  // Ensure the attached customer profiles are associated with the order owner
  // if they do not have a uid yet and the order does.
  if ($converted) {
    $wrapper = entity_metadata_wrapper('commerce_order', $order);

    foreach (field_info_instances('commerce_order', $order->type) as $field_name => $instance) {
      $field_info = field_info_field($field_name);

      if ($field_info['type'] == 'commerce_customer_profile_reference') {
        if (!is_null($wrapper->{$field_name}->value()) &&
          $wrapper->{$field_name}->uid->value() == 0) {
          $wrapper->{$field_name}->uid = $order->uid;
          $wrapper->{$field_name}->save();
        }
      }
    }
  }

  // Update the order status if specified.
  if ($form_state['values']['status'] != $form_state['values']['status_original']) {
    // We skip order saving in the update since we do it below once for the
    // entire form submission.
    commerce_order_status_update($order, $form_state['values']['status'], TRUE);
  }

  // Save the order.
  commerce_order_save($order);

  // Ensure the attached line items are associated with the order if they do not
  // have an order_id set yet.
  foreach (entity_metadata_wrapper('commerce_order', $order)->commerce_line_items as $delta => $line_item_wrapper) {
    if ($line_item_wrapper->order_id->value() == 0) {
      $line_item_wrapper->order_id = $order->order_id;
      commerce_line_item_save($line_item_wrapper->value());
    }
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
    drupal_set_message(t('Order @number could not be deleted.', array('@number' => $order->order_number)), 'error');
  }
}
