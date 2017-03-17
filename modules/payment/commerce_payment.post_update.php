<?php

/**
 * @file
 * Post update functions for Payment.
 */

/**
 * Update user Remote ID field data using payments gateways instead of module.
 *
 * @see https://www.drupal.org/node/2861181
 */
function commerce_payment_post_update_1(&$sandbox) {
  // Use the sandbox to store the information needed to track progression.
  if (!isset($sandbox['current'])) {
    // First check is any user has this field set.
    if ($count = \Drupal::entityQuery('user')->exists('commerce_remote_id')->count()->execute()) {
      // The count of entities visited so far.
      $sandbox['current'] = 0;
      // Total entities that must be visited.
      $sandbox['max'] = $count;
      // A place to store messages during the run.

      // Get the first payment gateway for every payment module.
      $payment_gateways_by_module = [];
      /** @var \Drupal\commerce_payment\PaymentGatewayStorageInterface $payment_gateway_storage */
      $payment_gateway_storage = \Drupal::entityTypeManager()->getStorage('commerce_payment_gateway');
      $payment_gateways = $payment_gateway_storage->loadMultiple();
      uasort($payment_gateways, [$payment_gateway_storage->getEntityType()->getClass(), 'sort']);
      foreach ($payment_gateways as $payment_gateway) {
        $payment_gateway_data = $payment_gateway->toArray();
        $module = reset($payment_gateway_data['dependencies']['module']);
        if (!isset($payment_gateways_by_module[$module])) {
          $payment_gateways_by_module[$module] = $payment_gateway;
        }
      }
      $sandbox['payment_gateways'] = $payment_gateways;
      $sandbox['payment_gateways_by_module'] = $payment_gateways_by_module;
      $sandbox['user_storage'] = \Drupal::entityTypeManager()->getStorage('user');
    }
    else {
      return t('No user Remote ID field data to update.');
    }
  }

  // Process entities by groups of 20.
  $limit = 20;
  // Get the user ids that have 'commerce_remote_id' field not empty.
  $result = \Drupal::entityQuery('user')
    ->exists('commerce_remote_id')
    ->range($sandbox['current'], $limit)
    ->execute();

  foreach ($result as $uid) {
    $user = $sandbox['user_storage']->load($uid);

    $save = FALSE;
    /** @var \Drupal\commerce\Plugin\Field\FieldType\RemoteIdFieldItemListInterface $remote_ids */
    $remote_ids = $user->commerce_remote_id;
    /** @var \Drupal\commerce\Plugin\Field\FieldType\RemoteIdItem $item */
    foreach ($remote_ids as $index => $item) {
      $current_provider = $item->getValue()['provider'];
      if (in_array($current_provider, array_keys($sandbox['payment_gateways_by_module']))) {
        $payment_gateway = $sandbox['payment_gateways_by_module'][$current_provider];
        $item->set('provider', $payment_gateway->id() . '-' . $payment_gateway->getPlugin()->getMode());
        $save = TRUE;
      }
      elseif (!in_array($current_provider, array_keys($sandbox['payment_gateways']))) {
        // Delete "orphaned" Remote Ids.
        $remote_ids->removeItem($index);
        $save = TRUE;
      }
    }
    if ($save) {
      $user->save();
    }

    // Update our progress information.
    $sandbox['current']++;
  }

  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['current'] / $sandbox['max']);

  if ($sandbox['#finished'] >= 1) {
    return t('The user Remote ID field data for payments updated.');
  }

}
