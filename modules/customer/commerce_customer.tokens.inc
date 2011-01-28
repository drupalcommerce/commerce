<?php
// $Id$

/**
 * @file
 * Builds placeholder replacement tokens for customer related data.
 */


/**
 * Implements hook_token_info().
 */
function commerce_customer_item_token_info() {
  $type = array(
    'name' => t('Customer profiles'),
    'description' => t('Tokens related to customer profiles.'),
    'needs-data' => 'customer',
  );

  // Tokens for customer profiles.
  $customer = array();

  $customer['customer-profile-id'] = array(
    'name' => t('Customer profile ID', array(), array('context' => 'a drupal customer profile')),
    'description' => t('The unique numeric ID of the customer profile.'),
  );
  $customer['revision-id'] = array(
    'name' => t('Revision ID'),
    'description' => t("The unique ID of the customer profile's latest revision."),
  );
  $customer['type'] = array(
    'name' => t('Customer profile type'),
    'description' => t('The type of the customer profile.'),
  );
  $customer['type-name'] = array(
    'name' => t('Customer profile type name'),
    'description' => t('The type name of the customer profile.'),
  );
  // Chained tokens for customer profiles.
  $customer['author'] = array(
    'name' => t('Customer profile author'),
    'description' => t('The author of the customer profile.'),
    'type' => 'user',
  );
  $customer['created'] = array(
    'name' => t('Date created'),
    'description' => t('The date the customer profile was created.'),
    'type' => 'date',
  );
  $customer['changed'] = array(
    'name' => t('Date updated'),
    'description' => t('The date the customer profile was last updated.'),
    'type' => 'date',
  );

  return array(
    'types' => array('customer' => $type),
    'tokens' => array('customer' => $customer),
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

  if ($type == 'customer' && !empty($data['customer'])) {
    $customer = $data['customer'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Simple key values on the customer profile.
        case 'customer-profile-id':
          $replacements[$original] = $customer->profile_id;
          break;

        case 'revision-id':
          $replacements[$original] = $customer->revision_id;
          break;

        case 'type':
          $replacements[$original] = $sanitize ? check_plain($customer->type) : $customer->type;
          break;

        case 'type-name':
          $types =commerce_customer_profile_types();
          $replacements[$original] = $types[$customer->type]->name;
          break;

        // Default values for the chained tokens handled below.
        case 'author':
          if ($customer->uid == 0) {
            $name = variable_get('anonymous', t('Anonymous'));
          }
          else {
            $account = user_load($customer->uid);
            $name = $account->name;
          }
          $replacements[$original] = $sanitize ? filter_xss($name) : $name;
          break;

        case 'created':
          $replacements[$original] = format_date($customer->created, 'medium', '', NULL, $language_code);
          break;

        case 'changed':
          $replacements[$original] = format_date($customer->changed, 'medium', '', NULL, $language_code);
          break;
      }
    }

    if ($author_tokens = token_find_with_prefix($tokens, 'author')) {
      $author = user_load($customer->uid);
      $replacements += token_generate('user', $author_tokens, array('user' => $author), $options);
    }

    foreach (array('created', 'changed') as $date) {
      if ($created_tokens = token_find_with_prefix($tokens, $date)) {
        $replacements += token_generate('date', $created_tokens, array('date' => $customer->{$date}), $options);
      }
    }
  }

  return $replacements;
}
