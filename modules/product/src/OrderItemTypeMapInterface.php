<?php

namespace Drupal\commerce_product;

/**
 * Provides a mapping between product and order item types.
 *
 * Relationship between entities:
 *   Product type -> Product variation type -> order item type.
 *
 * Maintaining a cached product type ID <-> order item type ID mapping allows
 * the system to skip loading the product type and product variation type
 * entities when determining the order item type to use for a product.
 */
interface OrderItemTypeMapInterface {

  /**
   * Gets the order item type ID for the given product type ID.
   *
   * @param string $product_type_id
   *   The product type ID.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the method is given an invalid product type ID.
   *
   * @return string
   *   The order item type ID.
   */
  public function getOrderItemTypeId($product_type_id);

  /**
   * Clears the cached mapping data.
   */
  public function clearCache();

}
