<?php

/**
 * @file
 * Contains \Drupal\commerce_store\Entity\EntityStoreInterface.
 */

namespace Drupal\commerce_store\Entity;

/**
 * Defines a common interface for entities that belong to a store.
 */
interface EntityStoreInterface {

  /**
   * Gets the store.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface|null
   *   The store entity, or null.
   */
  public function getStore();

  /**
   * Sets the store.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store entity.
   *
   * @return $this
   */
  public function setStore(StoreInterface $store);

  /**
   * Gets the store ID.
   *
   * @return int
   *   The store ID.
   */
  public function getStoreId();

  /**
   * Sets the store ID.
   *
   * @param int $storeId
   *   The store id.
   *
   * @return $this
   */
  public function setStoreId($storeId);

}
