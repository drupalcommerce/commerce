<?php

namespace Drupal\commerce_product\Entity;

use Drupal\commerce\Entity\CommerceBundleEntityInterface;

/**
 * Defines the interface for product variation types.
 */
interface ProductVariationTypeInterface extends CommerceBundleEntityInterface {

  /**
   * Gets the product variation type's order item type ID.
   *
   * Used for finding/creating the appropriate order item when purchasing a
   * product (adding it to an order).
   *
   * @return string
   *   The order item type ID.
   */
  public function getOrderItemTypeId();

  /**
   * Sets the product variation type's order item type ID.
   *
   * @param string $order_item_type_id
   *   The order item type ID.
   *
   * @return $this
   */
  public function setOrderItemTypeId($order_item_type_id);

  /**
   * Gets whether the product variation title should be automatically generated.
   *
   * @return bool
   *   Whether the product variation title should be automatically generated.
   */
  public function shouldGenerateTitle();

  /**
   * Sets whether the product variation title should be automatically generated.
   *
   * @param bool $generate_title
   *   Whether the product variation title should be automatically generated.
   *
   * @return $this
   */
  public function setGenerateTitle($generate_title);

}
