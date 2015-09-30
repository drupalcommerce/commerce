<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Event\FilterVariationsEvent.
 */

namespace Drupal\commerce_product\Event;

use Drupal\commerce_product\Entity\ProductInterface;
use Symfony\Component\EventDispatcher\Event;

class FilterVariationsEvent extends Event {

  /**
   * The parent product.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

  /**
   * The enabled variations.
   *
   * @var array
   */
  protected $variations;

  /**
   * Constructs a new FilterVariationsEvent object.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product.
   * @param array $variations
   *   The enabled variations.
   */
  public function __construct(ProductInterface $product, array $variations) {
    $this->product = $product;
    $this->variations = $variations;
  }

  /**
   * Sets the enabled variations.
   *
   * @param array $variations
   */
  public function setVariations(array $variations) {
    $this->variations = $variations;
  }

  /**
   * Gets the enabled variations.
   *
   * @return array
   */
  public function getVariations() {
    return $this->variations;
  }

}
