<?php

/**
 * @file
 * Checkout pane callback functions for the customer module.
 */


/**
 * Checkout pane callback: returns the customer profile pane's settings form.
 */
function commerce_customer_profile_pane_settings_form($checkout_pane) {
  $form = array();

  // Extract the type of profile represented by this pane from its ID.
  $type = substr($checkout_pane['pane_id'], 17);

  // Build an options array of customer profile reference fields available for
  // the data from this customer profile pane.
  $options = array('' => t('- None -'));

  foreach (commerce_info_fields('commerce_customer_profile_reference', 'commerce_order') as $name => $field) {
    if ($type == $field['settings']['profile_type']) {
      $instance = field_info_instance('commerce_order', $name, 'commerce_order');
      $options[$name] = check_plain($instance['label']);
    }
  }

  $form['commerce_' . $checkout_pane['pane_id'] . '_field'] = array(
    '#type' => 'select',
    '#title' => t('Related customer profile reference field'),
    '#description' => t('Specify the customer profile reference field on the order to populate with profile data from this checkout pane.'),
    '#options' => $options,
    '#default_value' => variable_get('commerce_' . $checkout_pane['pane_id'] . '_field', ''),
  );

  return $form;
}

/**
 * Checkout pane callback: returns a customer profile edit form.
 */
function commerce_customer_profile_pane_checkout_form($form, &$form_state, $checkout_pane, $order) {
  $pane_form = array('#parents' => array($checkout_pane['pane_id']));

  // Extract the type of profile represented by this pane from its ID.
  $type = substr($checkout_pane['pane_id'], 17);

  // Find the referenced profile using the related reference field...
  $wrapper = entity_metadata_wrapper('commerce_order', $order);
  $profile = NULL;

  // If the associated order field has been set...
  if ($field_name = variable_get('commerce_' . $checkout_pane['pane_id'] . '_field', '')) {
    $profile = $wrapper->{$field_name}->value();
  }
  else {
    // Or try the association stored in the order's data array if no field is set.
    if (!empty($order->data['profiles'][$checkout_pane['pane_id']])) {
      $profile = commerce_customer_profile_load($order->data['profiles'][$checkout_pane['pane_id']]);
    }
  }

  // Create a new profile of the specified type if it hasn't already been made.
  if (empty($profile)) {
    $profile = commerce_customer_profile_new($type, $order->uid);
  }

  // Add the entity context of the current cart order.
  $profile->entity_context = array(
    'entity_type' => 'commerce_order',
    'entity_id' => $order->order_id,
  );

  $pane_form['customer_profile'] = array(
    '#type' => 'value',
    '#value' => $profile,
  );

  // Add the field widgets for the profile.
  field_attach_form('commerce_customer_profile', $profile, $pane_form, $form_state);

  // Tweak the form to remove the fieldset from the address field if there
  // is only one on this profile.
  $addressfields = array();

  foreach (commerce_info_fields('addressfield', 'commerce_customer_profile') as $field_name => $field) {
    if (!empty($pane_form[$field_name]['#language'])) {
      $langcode = $pane_form[$field_name]['#language'];

      // Only consider this addressfield if it's represented on the form.
      if (!empty($pane_form[$field_name][$langcode])) {
        $addressfields[] = array($field_name, $langcode);
      }
    }
  }

  // Check to ensure only one addressfield was found on the form.
  if (count($addressfields) == 1) {
    list($field_name, $langcode) = array_shift($addressfields);

    foreach (element_children($pane_form[$field_name][$langcode]) as $delta) {
      // Don't mess with the "Add another item" button that could be present.
      if ($pane_form[$field_name][$langcode][$delta]['#type'] != 'submit') {
        $pane_form[$field_name][$langcode][$delta]['#type'] = 'container';
      }
    }
  }

  return $pane_form;
}

/**
 * Checkout pane callback: validates a customer profile edit form.
 */
function commerce_customer_profile_pane_checkout_form_validate($form, &$form_state, $checkout_pane, $order) {
  $profile = $form_state['values'][$checkout_pane['pane_id']]['customer_profile'];

  // Notify field widgets to validate their data.
  field_attach_form_validate('commerce_customer_profile', $profile, $form[$checkout_pane['pane_id']], $form_state);

  return TRUE;
}

/**
 * Checkout pane callback: submits a customer profile edit form.
 */
function commerce_customer_profile_pane_checkout_form_submit($form, &$form_state, $checkout_pane, $order) {
  $profile = $form_state['values'][$checkout_pane['pane_id']]['customer_profile'];

  // Ensure the profile is active.
  $profile->status = TRUE;

  // Set the profile's uid if it's being created at this time.
  if (empty($profile->profile_id)) {
    $profile->uid = $order->uid;
  }

  // Notify field widgets.
  field_attach_submit('commerce_customer_profile', $profile, $form[$checkout_pane['pane_id']], $form_state);

  // Save the profile.
  commerce_customer_profile_save($profile);

  // Store the profile ID for the related field as specified on the settings form.
  $wrapper = entity_metadata_wrapper('commerce_order', $order);

  if ($field_name = variable_get('commerce_' . $checkout_pane['pane_id'] . '_field', '')) {
    $wrapper->{$field_name} = $profile;
  }
  else {
    // Or make the association in the order's data array if no field was found.
    $order->data['profiles'][$checkout_pane['pane_id']] = $profile->profile_id;
  }
}

/**
 * Checkout pane callback: returns the cart contents review data for the
 *   Review checkout pane.
 */
function commerce_customer_profile_pane_review($form, $form_state, $checkout_pane, $order) {
  // Load the profile based on the related customer profile reference field...
  if ($field_name = variable_get('commerce_' . $checkout_pane['pane_id'] . '_field', '')) {
    $profile = entity_metadata_wrapper('commerce_order', $order)->{$field_name}->value();
  }
  else {
    // Or use the association stored in the order's data array if no field is set.
    $profile = commerce_customer_profile_load($order->data['profiles'][$checkout_pane['pane_id']]);
  }

  if ($profile) {
    $content = entity_view('commerce_customer_profile', array($profile->profile_id => $profile), 'customer');

    return drupal_render($content);
  }
  else {
    return t('No information');
  }
}
