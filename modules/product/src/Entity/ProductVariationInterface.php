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
   * Gets the variation price.
   *
   * @return object
   *   The variation price.
   */
  public function getPrice();

  /**
   * Gets whether the variation is active.
   *
   * Inactive variations are not visible on add to cart forms.
   *
   * @return bool
   *   TRUE if the variation is active, FALSE otherwise.
   */
  public function isActive();

  /**
   * Sets whether the variation is active.
   *
   * @param bool $active
   *   Whether the variation is active.
   *
   * @return $this
   */
  public function setActive($active);

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
