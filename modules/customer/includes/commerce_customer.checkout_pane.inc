<?php
// $Id$

/**
 * @file
 * Checkout pane callback functions for the customer module.
 */


/**
 * Checkout pane callback: returns a customer profile edit form.
 */
function commerce_customer_profile_pane_checkout_form($form, &$form_state, $checkout_pane, $order) {
  $pane_form = array();

  // Extract the type of profile represented by this pane from its ID.
	$type = substr($checkout_pane->pane_id, 17);

  // Create a new profile of the specified type if it hasn't already been made.
	if (!empty($order->data[$checkout_pane->pane_id])) {
		$profile = commerce_customer_profile_load($order->data[$checkout_pane->pane_id]);
	}
	else {
		$profile = commerce_customer_profile_new($type, $order->customer_uid);
	}

	$pane_form['customer_profile'] = array(
	  '#type' => 'value',
		'#value' => $profile,
	);

  // Add the field widgets for the profile.
  field_attach_form('commerce_customer_profile', $profile, $pane_form, $form_state);

  return $pane_form;
}

/**
 * Checkout pane callback: validates a customer profile edit form.
 */
function commerce_customer_profile_pane_checkout_form_validate($form, &$form_state, $checkout_pane, $order) {
  $profile = $form_state['values'][$checkout_pane->pane_id]['customer_profile'];

  // Store the form values locally to restore them after field submission.
  $values = $form_state['values'];

  // Temporarily make this pane's values the only values in the $form_state.
  $form_state['values'] = $form_state['values'][$checkout_pane->pane_id];

  // Notify field widgets to validate their data.
  field_attach_form_validate('commerce_customer_profile', $profile, $form[$checkout_pane->pane_id], $form_state);

  // Restore the values to the $form_state.
  $form_state['values'] = $values;

  return TRUE;
}

/**
 * Checkout pane callback: submits a customer profile edit form.
 */
function commerce_customer_profile_pane_checkout_form_submit($form, &$form_state, $checkout_pane, $order) {
  $profile = $form_state['values'][$checkout_pane->pane_id]['customer_profile'];

  // Ensure the profile is active.
  $profile->status = TRUE;

  // Set the profile's uid if it's being created at this time.
  if (empty($profile->profile_id)) {
    $profile->uid = $order->customer_uid;
  }

  // Store the form values locally to restore them after field submission.
  $values = $form_state['values'];

  // Temporarily make this pane's values the only values in the $form_state.
  $form_state['values'] = $form_state['values'][$checkout_pane->pane_id];

  // Notify field widgets.
  field_attach_submit('commerce_customer_profile', $profile, $form[$checkout_pane->pane_id], $form_state);

  // Restore the values to the $form_state.
  $form_state['values'] = $values;

  // Save the profile.
  commerce_customer_profile_save($profile);

	// Store the profile ID for this pane in $order->data for now.
	$order->data[$checkout_pane->pane_id] = $profile->profile_id;
}

/**
 * Checkout pane callback: returns the cart contents review data for the
 *   Review checkout pane.
 */
function commerce_customer_profile_pane_review($form, $form_state, $checkout_pane, $order) {
	$profile = commerce_customer_profile_load($order->data[$checkout_pane->pane_id]);

	return drupal_render(commerce_customer_profile_build_content($profile, 'customer'));
}
