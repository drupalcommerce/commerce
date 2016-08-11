<?php

namespace Drupal\commerce_product;

use Drupal\commerce_product\Entity\ProductInterface;

/**
 * Defines the interface for product variation storage.
 */
interface ProductVariationStorageInterface {

  /**
   * Loads the variation from context.
   *
   * Uses the variation specified in the URL (?v=) if it's active and
   * belongs to the current product.
   *
   * Note: The returned variation is not guaranteed to be enabled, the caller
   * needs to check it against the list from loadEnabled().
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The current product.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationInterface
   *   The product variation.
   */
  public function loadFromContext(ProductInterface $product);

  /**
   * Loads the enabled variations for the given product.
   *
   * Enabled variations are active variations that have been filtered through
   * the FILTER_VARIATIONS event.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationInterface[]
   *   The enabled variations.
   */
  public function loadEnabled(ProductInterface $product);

}
