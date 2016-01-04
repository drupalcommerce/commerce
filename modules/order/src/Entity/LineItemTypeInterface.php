<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Entity\LineItemTypeInterface.
 */

namespace Drupal\commerce_order\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for line item types.
 */
interface LineItemTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the line item type's purchasable entity type.
   *
   * For example, if line items of this type are used to purchase product
   * variations, the purchasable entity type will be 'commerce_product_variation'.
   *
   * @return string
   *   The purchasable entity type.
   */
  public function getPurchasableEntityType();

  /**
   * Sets the line item type's purchasable entity type.
   *
   * @param string $purchasable_entity_type
   *   The purchasable entity type.
   *
   * @return $this
   */
  public function setPurchasableEntityType($purchasable_entity_type);

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
   * @param string $order_type
   *   The order type.
   *
   * @return $this
   */
  public function setOrderType($order_type);

}
