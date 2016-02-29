<?php

namespace Drupal\commerce_product;

use Drupal\commerce_product\Entity\ProductInterface;

/**
 * Defines the interface for product variation storage.
 */
interface ProductVariationStorageInterface {

  /**
   * Loads the enabled variations for the given product.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationInterface[]
   *   The enabled variations.
   */
  public function loadEnabled(ProductInterface $product);

}
