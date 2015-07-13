<?php

/**
 * @file
 * Contains \Drupal\commerce_product\ProductInterface.
 */

namespace Drupal\commerce_product;

use Drupal\commerce_store\EntityStoreInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines the interface for products.
 */
interface ProductInterface extends EntityStoreInterface, EntityChangedInterface, EntityInterface, EntityOwnerInterface {

  /**
   * Gets the product type.
   *
   * @return string
   *   The product type.
   */
  public function getType();

  /**
   * Get the product title.
   *
   * @return string
   *   The product title
   */
  public function getTitle();

  /**
   * Set the product title.
   *
   * @param string $title
   *   The product title.
   *
   * @return $this
   */
  public function setTitle($title);

  /**
   * Get the product status.
   *
   * @return bool
   *   The product status
   */
  public function getStatus();

  /**
   * Set the product status.
   *
   * @param bool $status
   *   The product status.
   *
   * @return $this
   */
  public function setStatus($status);

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

}
