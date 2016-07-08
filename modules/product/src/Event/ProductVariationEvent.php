<?php

namespace Drupal\commerce_product\Event;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the product variation event.
 *
 * @see \Drupal\commerce_product\Event\ProductEvents
 */
class ProductVariationEvent extends Event {

  /**
   * The product variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $productVariation;

  /**
   * Constructs a new ProductVariationEvent.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $product_variation
   *   The product variation.
   */
  public function __construct(ProductVariationInterface $product_variation) {
    $this->productVariation = $product_variation;
  }

  /**
   * Gets the product variation.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationInterface
   *   The product variation.
   */
  public function getProductVariation() {
    return $this->productVariation;
  }

}
