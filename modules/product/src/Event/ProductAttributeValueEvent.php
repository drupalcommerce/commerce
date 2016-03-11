<?php

namespace Drupal\commerce_product\Event;

use Drupal\commerce_product\Entity\ProductAttributeValueInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the attributeValue attribute value event.
 *
 * @see \Drupal\commerce_product\Event\ProductEvents
 */
class ProductAttributeValueEvent extends Event {

  /**
   * The attributeValue.
   *
   * @var \Drupal\commerce_product\Entity\ProductAttributeValueInterface
   */
  protected $attributeValue;

  /**
   * Constructs a new ProductEvent.
   *
   * @param \Drupal\commerce_product\Entity\ProductAttributeValueInterface $attribute_value
   *   The attribute value.
   */
  public function __construct(ProductAttributeValueInterface $attribute_value) {
    $this->attributeValue = $attribute_value;
  }

  /**
   * The attribute value the event refers to.
   *
   * @return \Drupal\commerce_product\Entity\ProductAttributeValueInterface
   */
  public function getAttributeValue() {
    return $this->attributeValue;
  }

}
