<?php

/**
 * @file
 * Post update functions for Store.
 */

/**
 * Revert the Stores view because of the updated permission.
 */
function commerce_store_post_update_1() {
  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');

  $views = [
    'views.view.commerce_stores',
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
 * Revert the Stores view.
 */
function commerce_store_post_update_2() {
  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');

  $views = [
    'views.view.commerce_stores',
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
 * Revert the Store entity form display.
 */
function commerce_store_post_update_3() {
  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');

  $views = [
    'core.entity_form_display.commerce_store.online.default',
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
 * Set the default store and remove the default_store config key.
 */
function commerce_store_post_update_4() {
  /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
  $config_factory = \Drupal::service('config.factory');
  $uuid = $config_factory->get('commerce_store.settings')->get('default_store');
  if ($uuid) {
    $store_storage = \Drupal::entityTypeManager()->getStorage('commerce_store');
    /** @var \Drupal\commerce_store\Entity\StoreInterface[] $stores */
    $stores = $store_storage->loadByProperties(['uuid' => $uuid]);
    $store = reset($stores);
    if ($store) {
      $store->setDefault(TRUE);
      $store->save();
    }
  }
  $config_factory->getEditable('commerce_store.settings')->delete();
}
