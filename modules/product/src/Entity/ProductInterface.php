<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Entity\ProductInterface.
 */

namespace Drupal\commerce_product\Entity;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines the interface for products.
 */
interface ProductInterface extends EntityChangedInterface, EntityInterface, EntityOwnerInterface {

  /**
   * Gets the product title.
   *
   * @return string
   *   The product title
   */
  public function getTitle();

  /**
   * Sets the product title.
   *
   * @param string $title
   *   The product title.
   *
   * @return $this
   */
  public function setTitle($title);

  /**
   * Get whether the product is published.
   *
   * Unpublished products are only visible to their authors and administrators.
   *
   * @return bool
   *   TRUE if the product is published, FALSE otherwise.
   */
  public function isPublished();

  /**
   * Sets whether the product is published.
   *
   * @param bool $published
   *   Whether the product is published.
   *
   * @return $this
   */
  public function setPublished($published);

  /**
   * Gets the product creation timestamp.
   *
   * @return int
   *   The product creation timestamp.
   */
  public function getCreatedTime();

  /**
   * Sets the product creation timestamp.
   *
   * @param int $timestamp
   *   The product creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the stores through which the product is sold.
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
