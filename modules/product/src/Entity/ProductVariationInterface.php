<?php

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
   * Gets the variation title.
   *
   * @return string
   *   The variation title
   */
  public function getTitle();

  /**
   * Sets the variation title.
   *
   * @param string $title
   *   The variation title.
   *
   * @return $this
   */
  public function setTitle($title);

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
   * Gets the attribute IDs.
   *
   * @return int[]
   *   The attribute IDs, keyed by field name.
   */
  public function getAttributeIds();

  /**
   * Gets the attribute id for the given field name.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return int|null
   *   The attribute ID, or NULL.
   */
  public function getAttributeId($field_name);

  /**
   * Gets the attribute values.
   *
   * @return \Drupal\commerce_product\Entity\ProductAttributeValueInterface[]
   *   The attribute values, keyed by field name.
   */
  public function getAttributeValues();

  /**
   * Gets the attribute value for the given field name.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return \Drupal\commerce_product\Entity\ProductAttributeValueInterface|null
   *   The attribute value, or NULL.
   */
  public function getAttributeValue($field_name);

  /**
   * Gets an array of attribute field definitions.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   An array of attribute field definitions, keyed by field name.
   */
  public function getAttributeFieldDefinitions();

}
