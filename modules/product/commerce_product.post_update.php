<?php

/**
 * @file
 * Post update functions for Product.
 */

/**
 * Revert the Products view because of the updated permission.
 */
function commerce_product_post_update_1() {
  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');

  $views = [
    'views.view.commerce_products',
  ];
  $result = $config_updater->revert($views, FALSE);

  $success_results = $result->getSucceeded();
  $failure_results = $result->getFailed();
  $message = '';
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
 * Set the 'published' entity key.
 */
function commerce_product_post_update_2() {
  $definition_update_manager = \Drupal::entityDefinitionUpdateManager();
  $entity_type = $definition_update_manager->getEntityType('commerce_product');
  $keys = $entity_type->getKeys();
  $keys['published'] = 'status';
  $entity_type->set('entity_keys', $keys);
  $definition_update_manager->updateEntityType($entity_type);
}
