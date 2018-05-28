<?php

namespace Drupal\commerce_store;

use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines the interface for store storage.
 */
interface StoreStorageInterface extends ContentEntityStorageInterface {

  /**
   * Loads the default store.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface|null
   *   The default store, if known.
   */
  public function loadDefault();

  /**
   * Marks the provided store as the default.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The new default store.
   */
  public function markAsDefault(StoreInterface $store);

}
