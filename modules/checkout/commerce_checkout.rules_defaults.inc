<?php
// $Id$

/**
 * @file
 * Default rules configuration for Order.
 */

/**
 * Implements hook_default_rules_configuration().
 */
function commerce_checkout_default_rules_configuration() {
  $rules = array();

  // Add a reaction rule to update an order to the default status of the pending
  // order status upon checkout completion.
  $rule = rules_reaction_rule();

  $rule->label = t('Update the order status on checkout completion');
  $rule->active = TRUE;

  $rule
    ->event('commerce_checkout_complete')
    ->action('commerce_order_update_state', array(
      'order:select' => 'order',
      'order_state' => 'pending',
    ));

  $rules['commerce_checkout_order_status_update'] = $rule;

  // Add a reaction rule to assign an oder to a pre-existing user account if an
  // existing e-mail address is used in checkout.
  $rule = rules_reaction_rule();

  $rule->label = t('Assign an anonymous order to a pre-existing user');
  // TODO: Default this to TRUE once http://drupal.org/node/1044308 is fixed.
  $rule->active = FALSE;

  $rule
    ->event('commerce_checkout_complete')
    ->condition('entity_exists', array(
      'type' => 'user',
      'property' => 'mail',
      'value:select' => 'order:mail',
    ))
    ->condition('data_is', array(
      'data:select' => 'order:type',
      'op' => '==',
      'value' => 'commerce_order',
    ))
    ->action('entity_query', array(
      'type' => 'user',
      'property' => 'mail',
      'value:select' => 'order:mail',
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
      'data:select' => 'order:uid',
      'value:select' => 'list-item:uid',
    ));

  // Accommodate any profile types referenced by the order.
  $entity_info = entity_get_info('commerce_customer_profile');

  foreach ($entity_info['bundles'] as $profile_type => $data) {
    $loop
      ->action('data_set', array(
        'data:select' => 'order:' . strtr('customer-' . $profile_type, '_', '-') . ':uid',
        'value:select' => 'list-item:uid',
      ));
  }

  // Add the loop to the rule as an action.
  $rule->action($loop);

  $rules['commerce_checkout_order_convert'] = $rule;

  return $rules;
}
