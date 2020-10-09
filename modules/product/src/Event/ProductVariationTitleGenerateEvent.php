<?php

namespace Drupal\commerce_product\Event;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the product variation title generate event.
 *
 * @see \Drupal\commerce_product\Event\ProductEvents
 */
class ProductVariationTitleGenerateEvent extends Event {

  /**
   * The generated title.
   *
   * @var string
   */
  protected $title;

  /**
   * The product variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $productVariation;

  /**
   * Constructs a new ProductVariationEvent.
   *
   * @param string $title
   *   The generated title.
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $product_variation
   *   The product variation.
   */
  public function __construct($title, ProductVariationInterface $product_variation) {
    $this->title = $title;
    $this->productVariation = $product_variation;
  }

  /**
   * Gets the generated title.
   *
   * @return string
   *   The generated title.
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Updates the generated title.
   *
   * @param string $title
   *   The generated title
   */
  public function setTitle($title) {
    $this->title = $title;
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
