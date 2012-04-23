<?php

/**
 * @file
 * Forms for creating / editing and deleting customer profiles.
 */


/**
 * Form callback: create or edit a customer profile.
 *
 * @param $profile
 *   The profile object to edit or for a create form an empty profile object
 *     with only a profile type defined.
 */
function commerce_customer_customer_profile_form($form, &$form_state, $profile) {
  // Ensure this include file is loaded when the form is rebuilt from the cache.
  $form_state['build_info']['files']['form'] = drupal_get_path('module', 'commerce_customer') . '/includes/commerce_customer_profile.forms.inc';

  // Ensure the owner name is accessible if the uid is set.
  if (!empty($profile->uid) && $owner = user_load($profile->uid)) {
    $profile->name = $owner->name;
  }

  if (empty($profile->created)) {
    $profile->date = format_date(REQUEST_TIME, 'custom', 'Y-m-d H:i:s O');
  }

  // Add the field related form elements.
  $form_state['customer_profile'] = $profile;
  field_attach_form('commerce_customer_profile', $profile, $form, $form_state);

  $form['additional_settings'] = array(
    '#type' => 'vertical_tabs',
    '#weight' => 99,
  );

  // Add the user account and e-mail fields.
  $form['user'] = array(
    '#type' => 'fieldset',
    '#title' => t('User information'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#group' => 'additional_settings',
    '#attached' => array(
      'js' => array(
        drupal_get_path('module', 'commerce_customer') . '/commerce_customer.js',
        array(
          'type' => 'setting',
          'data' => array('anonymous' => variable_get('anonymous', t('Anonymous'))),
        ),
      ),
    ),
    '#weight' => 20,
  );
  $form['user']['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Owned by'),
    '#description' => t('Leave blank for %anonymous.', array('%anonymous' => variable_get('anonymous', t('Anonymous')))),
    '#maxlength' => 60,
    '#autocomplete_path' => 'user/autocomplete',
    '#default_value' => !empty($profile->name) ? $profile->name : '',
    '#weight' => -1,
  );

  // Add the status of the profile.
  $form['profile_status'] = array(
    '#type' => 'fieldset',
    '#title' => t('Status'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#group' => 'additional_settings',
    '#attached' => array(
      'js' => array(
        drupal_get_path('module', 'commerce_customer') . '/commerce_customer.js',
      ),
    ),
    '#weight' => 30,
  );
  $form['profile_status']['status'] = array(
    '#type' => 'radios',
    '#title' => t('Status'),
    '#description' => t('Disabled profiles will not be visible to customers in options lists.'),
    '#options' => array(
      '1' => t('Active'),
      '0' => t('Disabled'),
    ),
    '#default_value' => $profile->status,
    '#required' => TRUE,
    '#disabled' => !commerce_customer_profile_type_load($profile->type),
    '#weight' => 35,
  );

  // Disable the status field if the customer profile type has been disabled.
  if (!commerce_customer_profile_type_load($profile->type)) {
    $form['profile_status']['status']['#disabled'] = TRUE;
    $form['profile_status']['status']['#description'] .= '<br />' . t('This profile is of a type that is no longer available, so its status cannot be adjusted.');
  }

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
    '#value' => t('Save profile'),
    '#submit' => array_merge($submit, array('commerce_customer_customer_profile_form_submit')),
  );

  // We append the validate handler to #validate in case a form callback_wrapper
  // is used to add validate handlers earlier.
  $form['#validate'][] = 'commerce_customer_customer_profile_form_validate';

  return $form;
}

/**
 * Validation callback for commerce_customer_profile_form().
 */
function commerce_customer_customer_profile_form_validate($form, &$form_state) {
  $profile = $form_state['customer_profile'];

  // Validate the "owned by" field.
  if (!empty($form_state['values']['name']) && !($account = user_load_by_name($form_state['values']['name']))) {
    // The use of empty() is mandatory in the context of usernames as the empty
    // string denotes an anonymous user.
    form_set_error('name', t('The username %name does not exist.', array('%name' => $form_state['values']['name'])));
  }

  // Notify field widgets to validate their data.
  field_attach_form_validate('commerce_customer_profile', $profile, $form, $form_state);
}

/**
 * Submit callback for commerce_customer_profile_form().
 */
function commerce_customer_customer_profile_form_submit($form, &$form_state) {
  global $user;

  $profile = &$form_state['customer_profile'];

  // Save default parameters back into the $profile object.
  $profile->status = $form_state['values']['status'];

  // Set the profile's owner uid based on the supplied name.
  if (!empty($form_state['values']['name']) && $account = user_load_by_name($form_state['values']['name'])) {
    $profile->uid = $account->uid;
  }
  else {
    $profile->uid = 0;
  }

  // Notify field widgets.
  field_attach_submit('commerce_customer_profile', $profile, $form, $form_state);

  // Save the profile.
  commerce_customer_profile_save($profile);

  // Redirect based on the button clicked.
  drupal_set_message(t('Profile saved.'));
}

/**
 * Form callback: confirmation form for deleting a profile.
 *
 * @param $profile
 *   The profile object to be deleted.
 *
 * @see confirm_form()
 */
function commerce_customer_customer_profile_delete_form($form, &$form_state, $profile) {
  $form_state['customer_profile'] = $profile;

  // Ensure this include file is loaded when the form is rebuilt from the cache.
  $form_state['build_info']['files']['form'] = drupal_get_path('module', 'commerce_customer') . '/includes/commerce_customer_profile.forms.inc';

  $form['#submit'][] = 'commerce_customer_customer_profile_delete_form_submit';

  $form = confirm_form($form,
    t('Are you sure you want to delete this profile?'),
    '',
    '<p>' . t('Deleting this profile cannot be undone.') . '</p>',
    t('Delete'),
    t('Cancel'),
    'confirm'
  );

  return $form;
}

/**
 * Submit callback for commerce_customer_profile_delete_form().
 */
function commerce_customer_customer_profile_delete_form_submit($form, &$form_state) {
  $profile = $form_state['customer_profile'];

  if (commerce_customer_profile_delete($profile->profile_id)) {
    drupal_set_message(t('The profile has been deleted.'));
    watchdog('commerce_customer_profile', 'Deleted customer profile @profile_id.', array('@profile_id' => $profile->profile_id), WATCHDOG_NOTICE);
  }
  else {
    drupal_set_message(t('The profile could not be deleted.'), 'error');
  }
}
