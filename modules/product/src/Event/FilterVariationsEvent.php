<?php

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
   *   The parent product.
   * @param array $variations
   *   The enabled variations.
   */
  public function __construct(ProductInterface $product, array $variations) {
    $this->product = $product;
    $this->variations = $variations;
  }

  /**
   * Gets the parent product.
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface
   *   The parent product.
   */
  public function getProduct() {
    return $this->product;
  }

  /**
   * Gets the enabled variations.
   *
   * @return array
   *   The enabled variations.
   */
  public function getVariations() {
    return $this->variations;
  }

  /**
   * Sets the enabled variations.
   *
   * @param array $variations
   *   The enabled variations.
   *
   * @return $this
   */
  public function setVariations(array $variations) {
    $this->variations = $variations;
    return $this;
  }

}
