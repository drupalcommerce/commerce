<?php

namespace Drupal\commerce_store\Entity;

/**
 * Defines a common interface for entities that belong to stores.
 */
interface EntityStoresInterface {

  /**
   * Gets the stores.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface[]
   *   The stores.
   */
  public function getStores();

  /**
   * Sets the stores.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface[] $stores
   *   The stores.
   *
   * @return $this
   */
  public function setStores(array $stores);

  /**
   * Gets the store IDs.
   *
   * @return int[]
   *   The store IDs.
   */
  public function getStoreIds();

  /**
   * Sets the store IDs.
   *
   * @param int[] $store_ids
   *   The store IDs.
   *
   * @return $this
   */
  public function setStoreIds(array $store_ids);

}
