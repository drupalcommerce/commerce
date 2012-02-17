<?php

/**
 * @file
 * Default rule configurations for Checkout.
 */

/**
 * Implements hook_default_rules_configuration().
 */
function commerce_checkout_default_rules_configuration() {
  // Store the customer profile entity info for use in default rules.
  $customer_profile_entity_info = entity_get_info('commerce_customer_profile');

  $rules = array();

  // Add a reaction rule to update an order to the default status of the pending
  // order status upon checkout completion.
  $rule = rules_reaction_rule();

  $rule->label = t('Update the order status on checkout completion');
  $rule->active = TRUE;

  $rule
    ->event('commerce_checkout_complete')
    ->action('commerce_order_update_state', array(
      'commerce_order:select' => 'commerce-order',
      'order_state' => 'pending',
    ));

  $rules['commerce_checkout_order_status_update'] = $rule;

  // Add a reaction rule to assign an oder to a pre-existing user account if an
  // existing e-mail address is used in checkout.
  $rule = rules_reaction_rule();

  $rule->label = t('Assign an anonymous order to a pre-existing user');
  $rule->active = TRUE;

  $rule
    ->event('commerce_checkout_complete')
    ->condition('data_is', array(
      'data:select' => 'commerce-order:uid',
      'op' => '==',
      'value' => '0',
    ))
    ->condition('entity_exists', array(
      'type' => 'user',
      'property' => 'mail',
      'value:select' => 'commerce-order:mail',
    ))
    ->condition('data_is', array(
      'data:select' => 'commerce-order:type',
      'op' => '==',
      'value' => 'commerce_order',
    ))
    ->action('entity_query', array(
      'type' => 'user',
      'property' => 'mail',
      'value:select' => 'commerce-order:mail',
      'limit' => 1,
      'entity_fetched:label' => t('Fetched account'),
      'entity_fetched:var' => 'account_fetched',
    ));

  // Build a loop that updates the order and customer profile uids with the uid
  // from the fetched user account.
  $loop = rules_loop(array(
    'list:select' => 'account-fetched',
    'item:var' => 'list_item',
    'item:label' => t('Current list item'),
    'item:type' => 'user',
  ))
    ->action('data_set', array(
      'data:select' => 'commerce-order:uid',
      'value:select' => 'list-item:uid',
    ));

  // Accommodate any profile types referenced by the order.
  foreach ($customer_profile_entity_info['bundles'] as $type => $data) {
    $instance = field_info_instance('commerce_order', 'commerce_customer_' . $type, 'commerce_order');

    if (!empty($instance)) {
      $loop
        ->action('data_set', array(
          'data:select' => 'commerce-order:' . strtr('commerce-customer-' . $type, '_', '-') . ':uid',
          'value:select' => 'list-item:uid',
        ));
    }
  }

  // Add the loop to the rule as an action.
  $rule->action($loop);

  // Adjust the weight so this rule executes after the order status has been
  // updated.
  $rule->weight = 1;

  $rules['commerce_checkout_order_convert'] = $rule;

  // Add a reaction rule that creates a new user account during checkout
  // completion if the customer specified a non-existent e-mail address. The
  // default functionality is to create an active user account with the e-mail
  // for administrator created accounts and will always assume the need for
  // e-mail verification for setting a password.
  $rule = rules_reaction_rule();

  $rule->label = t('Create a new account for an anonymous order');
  $rule->active = TRUE;

  $rule
    ->event('commerce_checkout_complete')
    ->condition('data_is', array(
      'data:select' => 'commerce-order:uid',
      'op' => '==',
      'value' => '0',
    ))
    ->condition(rules_condition('entity_exists', array(
      'type' => 'user',
      'property' => 'mail',
      'value:select' => 'commerce-order:mail',
    ))->negate())
    ->condition('data_is', array(
      'data:select' => 'commerce-order:type',
      'op' => '==',
      'value' => 'commerce_order',
    ))
    ->action('entity_create', array(
      'type' => 'user',
      'param_name:select' => 'commerce-order:mail-username',
      'param_mail:select' => 'commerce-order:mail',
      'entity_created:label' => t('Created account'),
      'entity_created:var' => 'account_created',
    ))
    ->action('data_set', array(
      'data:select' => 'account-created:status',
      'value' => 1,
    ))
    ->action('entity_save', array(
      'data:select' => 'account-created',
      'immediate' => 1,
    ))
    ->action('entity_query', array(
      'type' => 'user',
      'property' => 'mail',
      'value:select' => 'commerce-order:mail',
      'limit' => 1,
      'entity_fetched:label' => t('Fetched account'),
      'entity_fetched:var' => 'account_fetched',
    ));

  // Build a loop that send the account notification e-mail and updates the
  // order and customer profile uids with the uid from the fetched user account.
  $loop = rules_loop(array(
    'list:select' => 'account-fetched',
    'item:var' => 'list_item',
    'item:label' => t('Current list item'),
    'item:type' => 'user',
  ))
    ->action('send_account_email', array(
      'account:select' => 'list-item',
      'email_type' => 'register_admin_created',
    ))
    ->action('data_set', array(
      'data:select' => 'commerce-order:uid',
      'value:select' => 'list-item:uid',
    ));

  // Accommodate any profile types referenced by the order.
  foreach ($customer_profile_entity_info['bundles'] as $type => $data) {
    $instance = field_info_instance('commerce_order', 'commerce_customer_' . $type, 'commerce_order');

    if (!empty($instance)) {
      $loop
        ->action('data_set', array(
          'data:select' => 'commerce-order:' . strtr('commerce-customer-' . $type, '_', '-') . ':uid',
          'value:select' => 'list-item:uid',
        ));
    }
  }

  // Add the loop to the rule as an action.
  $rule->action($loop);

  // Adjust the weight so this rule executes after the one checking for a pre-
  // existing user account.
  $rule->weight = 2;

  $rules['commerce_checkout_new_account'] = $rule;

  // Add a reaction rule to send order e-mail upon checkout completion.
  $rule = rules_reaction_rule();

  $rule->label = t('Send an order notification e-mail');
  $rule->active = TRUE;

  $rule
    ->event('commerce_checkout_complete')
    ->action('mail', array(
      'to:select' => 'commerce-order:mail',
      'subject' => t('Order [commerce-order:order-number] at [site:name]'),
      'message' => t("Thanks for your order [commerce-order:order-number] at [site:name].\n\nIf this is your first order with us, you will receive a separate e-mail with login instructions. You can view your order history with us at any time by logging into our website at:\n\n[site:login-url]\n\nYou can find the status of your current order at:\n\n[commerce-order:customer-url]\n\nPlease contact us if you have any questions about your order."),
      'from' => '',
    ));

  // Adjust the weight so this rule executes after the order has been updated to
  // the proper user account.
  $rule->weight = 4;

  $rules['commerce_checkout_order_email'] = $rule;

  return $rules;
}
