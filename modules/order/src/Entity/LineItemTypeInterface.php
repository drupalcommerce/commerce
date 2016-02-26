<?php

namespace Drupal\commerce_order\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for line item types.
 */
interface LineItemTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the line item type's purchasable entity type ID.
   *
   * E.g, if line items of this type are used to purchase product variations,
   * the purchasable entity type ID will be 'commerce_product_variation'.
   *
   * @return string
   *   The purchasable entity type ID.
   */
  public function getPurchasableEntityTypeId();

  /**
   * Sets the line item type's purchasable entity type ID.
   *
   * @param string $purchasable_entity_type_id
   *   The purchasable entity type.
   *
   * @return $this
   */
  public function setPurchasableEntityTypeId($purchasable_entity_type_id);

  /**
   * Gets the line item type's order type ID.
   *
   * @return string
   *   The order type.
   */
  public function getOrderTypeId();

  /**
   * Sets the line item type's order type ID.
   *
   * @param string $order_type_id
   *   The order type ID.
   *
   * @return $this
   */
  public function setOrderTypeId($order_type_id);

}
