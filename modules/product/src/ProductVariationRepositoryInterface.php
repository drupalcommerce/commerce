<?php


namespace Drupal\commerce_product;


use Drupal\commerce_product\Entity\ProductInterface;

/**
 * Provides a repository for retrieving variations.
 *
 * To keep BC, this is a proxy to the matching entity storage methods, but
 * provides support for ensuring the entities are properly translated.
 */
interface ProductVariationRepositoryInterface {

  /**
   * Loads the product variation for the given SKU.
   *
   * @param string $sku
   *   The SKU.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationInterface|null
   *   The product variation, or NULL if not found.
   */
  public function loadBySku($sku);

  /**
   * Loads the product variation from context.
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
   * Loads the enabled product variations for the given product.
   *
   * Enabled variations are active variations that have been filtered through
   * the FILTER_VARIATIONS event.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationInterface[]
   *   The enabled product variations.
   */
  public function loadEnabled(ProductInterface $product);

}
