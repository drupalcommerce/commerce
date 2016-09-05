<?php

namespace Drupal\commerce_store\Entity;

/**
 * Defines a common interface for entities that belong to a store.
 */
interface EntityStoresInterface {

  /**
   * Gets the stores through which the purchasable entity is sold.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface[]
   *   The stores.
   */
  public function getStores();

  /**
   * Sets the stores through which the product is sold.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface[] $stores
   *   The stores through which the product is sold.
   *
   * @return $this
   */
  public function setStores(array $stores);

  /**
   * Gets the ids of stores through which the product is sold.
   *
   * @return int[]
   *   The ids of stores through which the product is sold.
   */
  public function getStoreIds();

  /**
   * Sets the ids of stores through which the product is sold.
   *
   * @param int[] $store_ids
   *   The ids of stores through which the product is sold.
   *
   * @return $this
   */
  public function setStoreIds(array $store_ids);

}
