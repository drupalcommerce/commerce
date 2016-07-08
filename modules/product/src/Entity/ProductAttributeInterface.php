<?php

namespace Drupal\commerce_product\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for product attributes.
 */
interface ProductAttributeInterface extends ConfigEntityInterface {

  /**
   * Gets the attribute values.
   *
   * @return \Drupal\commerce_product\Entity\ProductAttributeValueInterface[]
   *   The attribute values.
   */
  public function getValues();

  /**
   * Gets the attribute element type.
   *
   * @return string
   *   The element type name.
   */
  public function getElementType();

}
