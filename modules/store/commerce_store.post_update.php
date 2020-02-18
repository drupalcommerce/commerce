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
  $result = $config_updater->revert([
    'views.view.commerce_stores',
  ], FALSE);
  $message = implode('<br>', $result->getFailed());

  return $message;
}

/**
 * Revert the Stores view.
 */
function commerce_store_post_update_2() {
  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');
  $result = $config_updater->revert([
    'views.view.commerce_stores',
  ], FALSE);
  $message = implode('<br>', $result->getFailed());

  return $message;
}

/**
 * Revert the Store entity form display.
 */
function commerce_store_post_update_3() {
  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');
  $result = $config_updater->revert([
    'core.entity_form_display.commerce_store.online.default',
  ], FALSE);
  $message = implode('<br>', $result->getFailed());

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
