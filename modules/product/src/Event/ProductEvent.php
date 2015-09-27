<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Event\ProductEvent.
 */

namespace Drupal\commerce_product\Event;

use Drupal\commerce_product\Entity\ProductInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the product event.
 *
 * @see \Drupal\commerce_product\Event\ProductEvents
 */
class ProductEvent extends Event {

  /**
   * The product.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

  /**
   * Constructs a new ProductEvent.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product.
   */
  public function __construct(ProductInterface $product) {
    $this->product = $product;
  }

  /**
   * The product the event refers to.
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface
   */
  public function getProduct() {
    return $this->product;
  }

}
