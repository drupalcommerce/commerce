<?php

namespace Drupal\commerce_product\Event;

use Drupal\commerce_product\Entity\ProductAttributeValueInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the product attribute value event.
 *
 * @see \Drupal\commerce_product\Event\ProductEvents
 */
class ProductAttributeValueEvent extends Event {

  /**
   * The product attribute value.
   *
   * @var \Drupal\commerce_product\Entity\ProductAttributeValueInterface
   */
  protected $attributeValue;

  /**
   * Constructs a new ProductAttributeValueEvent.
   *
   * @param \Drupal\commerce_product\Entity\ProductAttributeValueInterface $attribute_value
   *   The product attribute value.
   */
  public function __construct(ProductAttributeValueInterface $attribute_value) {
    $this->attributeValue = $attribute_value;
  }

  /**
   * Gets the product attribute value.
   *
   * @return \Drupal\commerce_product\Entity\ProductAttributeValueInterface
   *   The product attribute value.
   */
  public function getAttributeValue() {
    return $this->attributeValue;
  }

}
