<?php

namespace Drupal\commerce_product\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for product variation types.
 */
interface ProductVariationTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the product variation type's line item type ID.
   *
   * Used for finding/creating the appropriate line item when purchasing a
   * product (adding it to an order).
   *
   * @return string
   *   The line item type ID.
   */
  public function getLineItemTypeId();

  /**
   * Sets the product variation type's line item type ID.
   *
   * @param string $line_item_type_id
   *   The line item type ID.
   *
   * @return $this
   */
  public function setLineItemTypeId($line_item_type_id);

}
