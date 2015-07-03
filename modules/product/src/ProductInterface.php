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
   * Get the SKU of this product.
   *
   * @return string
   *   The product SKU
   */
  public function getSku();

  /**
   * Set the SKU of this product
   *
   * @param string $sku
   *   The product SKU
   *
   * @return \Drupal\commerce_product\ProductInterface
   */
  public function setSku($sku);

  /**
   * Get the title of this product.
   *
   * @return string
   *   The product title
   */
  public function getTitle();

  /**
   * Set the title of this product
   *
   * @param string $title
   *   The product title
   *
   * @return \Drupal\commerce_product\ProductInterface
   */
  public function setTitle($title);

  /**
   * Get the status of this product.
   *
   * @return boolean
   *   The product status
   */
  public function getStatus();

  /**
   * Set the status of this product
   *
   * @param boolean $status
   *   The product status
   *
   * @return \Drupal\commerce_product\ProductInterface
   */
  public function setStatus($status);

  /**
   * Gets the product creation timestamp.
   *
   * @return int
   *   Creation timestamp of the product.
   */
  public function getCreatedTime();

  /**
   * Sets the product creation timestamp.
   *
   * @param int $timestamp
   *   The product creation timestamp.
   *
   * @return \Drupal\commerce_product\ProductInterface
   *   The called product entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the product type.
   *
   * @return string
   *   The product type.
   */
  public function getType();

}
