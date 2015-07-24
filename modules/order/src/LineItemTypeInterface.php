<?php

/**
 * @file
 * Contains \Drupal\commerce_order\LineItemTypeInterface.
 */

namespace Drupal\commerce_order;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for line item types.
 */
interface LineItemTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the line item type's source entity type.
   *
   * For example, if line items of this type are populated from product
   * variations, the source entity type will be 'commerce_product_variation'.
   *
   * @return string
   *   The source entity type.
   */
  public function getSourceEntityType();

  /**
   * Sets the line item type's source entity type.
   *
   * @param string $sourceEntityType
   *   The source entity type.
   *
   * @return $this
   */
  public function setSourceEntityType($sourceEntityType);

  /**
   * Gets the line item type's order type.
   *
   * @return string
   *   The order type.
   */
  public function getOrderType();

  /**
   * Sets the line item type's order type.
   *
   * @param string $orderType
   *   The order type.
   *
   * @return $this
   */
  public function setOrderType($orderType);

}
