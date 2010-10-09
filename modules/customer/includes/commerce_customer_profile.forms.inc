<?php
// $Id$

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

  // Add the default field elements.

  // Add the field related form elements.
  $form_state['customer_profile'] = $profile;
  field_attach_form('commerce_customer_profile', $profile, $form, $form_state);

  $form['status'] = array(
    '#type' => 'radios',
    '#title' => t('Status'),
    '#description' => t('Disabled profiles will not be visible to customers.'),
    '#options' => array(
      '1' => t('Active'),
      '0' => t('Disabled'),
    ),
    '#default_value' => $profile->status,
    '#required' => TRUE,
    '#weight' => 35,
  );

  $form['actions'] = array(
    '#type' => 'container',
    '#attributes' => array('class' => array('form-actions')),
    '#weight' => 40,
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
    '#submit' => $submit + array('commerce_customer_customer_profile_form_submit'),
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

  // Set the profile's uid if it's being created at this time.
  if (empty($profile->profile_id)) {
    $profile->uid = $user->uid;
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

  $form['#submit'][] = 'commerce_customer_profile_delete_form_submit';

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
