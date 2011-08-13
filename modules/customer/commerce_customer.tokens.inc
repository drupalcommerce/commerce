<?php

/**
 * @file
 * Builds placeholder replacement tokens for customer related data.
 */


/**
 * Implements hook_token_info().
 */
function commerce_customer_token_info() {
  $type = array(
    'name' => t('Customer profiles'),
    'description' => t('Tokens related to customer profiles.'),
    'needs-data' => 'commerce-customer-profile',
  );

  // Tokens for customer profiles.
  $profile = array();

  $profile['customer-profile-id'] = array(
    'name' => t('Customer profile ID'),
    'description' => t('The unique numeric ID of the customer profile.'),
  );
  $profile['revision-id'] = array(
    'name' => t('Revision ID'),
    'description' => t("The unique ID of the customer profile's latest revision."),
  );
  $profile['type'] = array(
    'name' => t('Customer profile type'),
    'description' => t('The type of the customer profile.'),
  );
  $profile['type-name'] = array(
    'name' => t('Customer profile type name'),
    'description' => t('The type name of the customer profile.'),
  );
  // Chained tokens for customer profiles.
  $profile['owner'] = array(
    'name' => t('Customer profile owner'),
    'description' => t('The user the customer profile belongs to.'),
    'type' => 'user',
  );
  $profile['created'] = array(
    'name' => t('Date created'),
    'description' => t('The date the customer profile was created.'),
    'type' => 'date',
  );
  $profile['changed'] = array(
    'name' => t('Date updated'),
    'description' => t('The date the customer profile was last updated.'),
    'type' => 'date',
  );

  return array(
    'types' => array('commerce-customer-profile' => $type),
    'tokens' => array('commerce-customer-profile' => $profile),
  );
}

/**
 * Implements hook_tokens().
 */
function commerce_customer_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $url_options = array('absolute' => TRUE);

  if (isset($options['language'])) {
    $url_options['language'] = $options['language'];
    $language_code = $options['language']->language;
  }
  else {
    $language_code = NULL;
  }

  $sanitize = !empty($options['sanitize']);

  $replacements = array();

  if ($type == 'commerce-customer-profile' && !empty($data['commerce-customer-profile'])) {
    $profile = $data['commerce-customer-profile'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Simple key values on the customer profile.
        case 'customer-profile-id':
          $replacements[$original] = $profile->profile_id;
          break;

        case 'revision-id':
          $replacements[$original] = $profile->revision_id;
          break;

        case 'type':
          $replacements[$original] = $sanitize ? check_plain($profile->type) : $profile->type;
          break;

        case 'type-name':
          $replacements[$original] = commerce_customer_profile_type_get_name($profile->type);
          break;

        // Default values for the chained tokens handled below.
        case 'owner':
          if ($profile->uid == 0) {
            $name = variable_get('anonymous', t('Anonymous'));
          }
          else {
            $account = user_load($profile->uid);
            $name = $account->name;
          }
          $replacements[$original] = $sanitize ? filter_xss($name) : $name;
          break;

        case 'created':
          $replacements[$original] = format_date($profile->created, 'medium', '', NULL, $language_code);
          break;

        case 'changed':
          $replacements[$original] = format_date($profile->changed, 'medium', '', NULL, $language_code);
          break;
      }
    }

    if ($owner_tokens = token_find_with_prefix($tokens, 'owner')) {
      $owner = user_load($profile->uid);
      $replacements += token_generate('user', $owner_tokens, array('user' => $owner), $options);
    }

    foreach (array('created', 'changed') as $date) {
      if ($created_tokens = token_find_with_prefix($tokens, $date)) {
        $replacements += token_generate('date', $created_tokens, array('date' => $profile->{$date}), $options);
      }
    }
  }

  return $replacements;
}
