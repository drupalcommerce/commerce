<?php

/**
 * @file
 * Contains \Drupal\commerce\CommerceProductInterface.
 */

namespace Drupal\commerce_product;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides an interface defining a Commerce Product entity.
 */
interface CommerceProductInterface extends EntityInterface {
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
   * @return \Drupal\commerce_product\CommerceProductInterface
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
   * @return \Drupal\commerce_product\CommerceProductInterface
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
   * @return \Drupal\commerce_product\CommerceProductInterface
   */
  public function setStatus($status);

 /**
   * Get the description of this product.
   * 
   * @return string
   *   The product description
   */
  public function getDescription();
  
  /**
   * Set the description of this product
   * 
   * @param string $description
   *   The product description
   * 
   * @return \Drupal\commerce_product\CommerceProductInterfaceInterface
   */
  public function setDescription($description);  

  /**
   * Returns the product creation timestamp.
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
   * @return \Drupal\product\CommerceProductInterfaceInterface
   *   The called product entity.
   */
  public function setCreatedTime($timestamp);
  
  /**
   * Returns the product type.
   *
   * @return string
   *   The product type.
   */
  public function getType();
  
  /**
   * Get the product data property.
   * 
   * @return array
   *   Unstructured array of product data
   */
  public function getData();

  // @TODO do we need a setter for data? I can't see how this would be useful
  // unless it was something where you could tell it what part of the data array
  // you wanted to set/replace.
  
  /**
   * Returns the product uid
   * 
   * @return integer
   *   Product user id
   */
  public function getUid();
  
  /**
   * Sets the product uid
   * 
   * @param integer $uid
   *   User id
   * 
   * @return \Drupal\product\CommerceProductInterfaceInterface
   */
  public function setUid($uid);
}
