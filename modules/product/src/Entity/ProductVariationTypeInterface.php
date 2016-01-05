<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Entity\ProductVariationTypeInterface.
 */

namespace Drupal\commerce_product\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for product variation types.
 */
interface ProductVariationTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the product variation type's line item type.
   *
   * Used for finding/creating the appropriate line item when purchasing a
   * product (adding it to an order).
   *
   * @return string
   *   The line item type.
   */
  public function getLineItemType();

  /**
   * Sets the product variation type's line item type.
   *
   * @param string $line_item_type
   *   The line item type.
   *
   * @return $this
   */
  public function setLineItemType($line_item_type);

}
