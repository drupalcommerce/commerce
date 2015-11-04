<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Entity\ProductVariationInterface.
 */

namespace Drupal\commerce_product\Entity;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines the interface for product variations.
 */
interface ProductVariationInterface extends PurchasableEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the variation type.
   *
   * @return string
   *   The variation type.
   */
  public function getType();

  /**
   * Gets the parent product.
   *
   * @return ProductInterface|null
   *   The product entity, or null.
   */
  public function getProduct();

  /**
   * Gets the parent product id.
   *
   * @return int|null
   *   The product id, or null.
   */
  public function getProductId();

  /**
   * Get the variation SKU.
   *
   * @return string
   *   The variation SKU.
   */
  public function getSku();

  /**
   * Set the variation SKU.
   *
   * @param string $sku
   *   The variation SKU.
   *
   * @return $this
   */
  public function setSku($sku);

  /**
   * Get the variation price.
   *
   * @return object
   *   The variation price.
   */
  public function getPrice();

  /**
   * Set the variation price.
   *
   * @param object $price
   *   The variation price.
   *
   * @return $this
   */
  public function setPrice($price);

  /**
   * Get the variation status.
   *
   * @return bool
   *   The variation status
   */
  public function getStatus();

  /**
   * Set the variation status.
   *
   * @param bool $status
   *   The variation status.
   *
   * @return $this
   */
  public function setStatus($status);

  /**
   * Gets the variation creation timestamp.
   *
   * @return int
   *   The variation creation timestamp.
   */
  public function getCreatedTime();

  /**
   * Sets the variation creation timestamp.
   *
   * @param int $timestamp
   *   The variation creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets an array of field item lists for attribute fields.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface[]
   *   An array of field item lists for attribute fields, keyed by field name.
   */
  public function getAttributeFields();

  /**
   * Gets an array of attribute field definitions.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   An array of attribute field definitions, keyed by field name.
   */
  public function getAttributeFieldDefinitions();

}
