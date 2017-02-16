<?php

namespace Drupal\commerce_product\Entity;

use Drupal\commerce_store\Entity\EntityStoresInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines the interface for products.
 */
interface ProductInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface, EntityStoresInterface {

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
   * Gets the variation IDs.
   *
   * @return int[]
   *   The variation IDs.
   */
  public function getVariationIds();

  /**
   * Gets the variations.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationInterface[]
   *   The variations.
   */
  public function getVariations();

  /**
   * Sets the variations.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface[] $variations
   *   The variations.
   *
   * @return $this
   */
  public function setVariations(array $variations);

  /**
   * Gets whether the product has variations.
   *
   * A product must always have at least one variation, but a newly initialized
   * (or invalid) product entity might not have any.
   *
   * @return bool
   *   TRUE if the product has variations, FALSE otherwise.
   */
  public function hasVariations();

  /**
   * Adds a variation.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The variation.
   *
   * @return $this
   */
  public function addVariation(ProductVariationInterface $variation);

  /**
   * Removes a variation.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The variation.
   *
   * @return $this
   */
  public function removeVariation(ProductVariationInterface $variation);

  /**
   * Checks whether the product has a given variation.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The variation.
   *
   * @return bool
   *   TRUE if the variation was found, FALSE otherwise.
   */
  public function hasVariation(ProductVariationInterface $variation);

  /**
   * Gets the default variation.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationInterface
   *   The default variation.
   */
  public function getDefaultVariation();

}
