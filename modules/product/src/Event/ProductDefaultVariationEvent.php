<?php

namespace Drupal\commerce_product\Event;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Symfony\Component\EventDispatcher\Event;

class ProductDefaultVariationEvent extends Event {

  /**
   * The default product variation or null.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface|null
   */
  protected $defaultVariation;

  /**
   * The product.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

  /**
   * Constructs a new ProductDefaultVariationEvent object.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface|null $default_variation
   *   The product variation or null.
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product.
   */
  public function __construct($default_variation, ProductInterface $product) {
    $this->defaultVariation = $default_variation;
    $this->product = $product;
  }

  /**
   * Gets the product.
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface
   *   The product.
   */
  public function getProduct() {
    return $this->product;
  }

  /**
   * Set the default product variation.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $default_variation
   *   The variation.
   */
  public function setDefaultVariation(ProductVariationInterface $default_variation) {
    $this->defaultVariation = $default_variation;
  }

  /**
   * Gets the default product variation.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationInterface|null
   *   The default variation, or NULL if none found.
   */
  public function getDefaultVariation() {
    return $this->defaultVariation;
  }

}
