<?php

/**
 * @file
 * Contains \Drupal\commerce_store\StoreStorageInterface.
 */

namespace Drupal\commerce_store;

/**
 * Defines the interface for store storage.
 */
interface StoreStorageInterface {

  /**
   * Loads the default store.
   *
   * @return \Drupal\commerce_store\StoreInterface|null
   *   The default store, if known.
   */
  public function loadDefault();

  /**
   * Marks the provided store as the default.
   *
   * @param \Drupal\commerce_store\StoreInterface $store
   *   The new default store.
   */
  public function markAsDefault(StoreInterface $store);

}
