<?php

/**
 * @file
 * Post update functions for Order.
 */

/**
 * Revert Order views to fix broken Price fields.
 */
function commerce_order_post_update_1() {
  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');

  $views = [
    'views.view.commerce_order_item_table',
    'views.view.commerce_user_orders',
    'views.view.commerce_orders',
  ];
  $result = $config_updater->revert($views, FALSE);

  $success_results = $result->getSucceeded();
  $failure_results = $result->getFailed();
  if ($success_results) {
    $message = t('Succeeded:') . '<br>';
    foreach ($success_results as $success_message) {
      $message .= $success_message . '<br>';
    }
    $message .= '<br>';
  }
  if ($failure_results) {
    $message .= t('Failed:') . '<br>';
    foreach ($failure_results as $failure_message) {
      $message .= $failure_message . '<br>';
    }
  }

  return $message;
}

/**
 * Update order types.
 */
function commerce_order_post_update_2() {
  $entity_type_manager = \Drupal::entityTypeManager();
  $order_type_storage = $entity_type_manager->getStorage('commerce_order_type');
  /** @var \Drupal\commerce_order\Entity\OrderTypeInterface[] $order_types */
  $order_types = $order_type_storage->loadMultiple();
  foreach ($order_types as $order_type) {
    if ($order_type->getRefreshMode() == 'owner_only') {
      $order_type->setRefreshMode('customer');
      $order_type->save();
    }
  }
}
