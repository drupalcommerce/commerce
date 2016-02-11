<?php
namespace Drupal\commerce_product;
use Drupal\commerce_product\Entity\ProductInterface;

/**
 * Defines the interface for product variation managers.
 */
interface ProductVariationManagerInterface {

  /**
   * Gets a list of enabled variations for the given product.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product.
   *
   * @return array
   *   Array of enabled variations.
   */
  public function getEnabledVariations(ProductInterface $product);

}
