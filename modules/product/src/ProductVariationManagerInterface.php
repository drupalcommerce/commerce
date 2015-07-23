<?php

/**
 * @file
 * Contains \Drupal\commerce_product\ProductVariationManagerInterface.
 */

namespace Drupal\commerce_product;

/**
 * Defines the interface for product variation managers.
 */
interface ProductVariationManagerInterface {

  /**
   * Gets a list of enabled variations for the given product.
   *
   * @param \Drupal\commerce_product\ProductInterface $product
   *   The product.
   *
   * @return array
   *   Array of enabled variations.
   */
  public function getEnabledVariations(ProductInterface $product);

}
