<?php

namespace Drupal\commerce;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the interface for purchasable entities.
 *
 * Lives in Drupal\commerce instead of Drupal\commerce_order so that entity
 * type providing modules such as commerce_product don't need to depend
 * on commerce_order.
 */
interface PurchasableEntityInterface extends ContentEntityInterface {

  /**
   * Gets the stores through which the purchasable entity is sold.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface[]
   *   The stores.
   */
  public function getStores();

  /**
   * Gets the purchasable entity's order item type ID.
   *
   * Used for finding/creating the appropriate order item when purchasing a
   * product (adding it to an order).
   *
   * @return string
   *   The order item type ID.
   */
  public function getOrderItemTypeId();

  /**
   * Gets the purchasable entity's order item title.
   *
   * Saved in the $order_item->title field to protect the order items of
   * completed orders against changes in the referenced purchased entity.
   *
   * @return string
   *   The order item title.
   */
  public function getOrderItemTitle();

  /**
   * Gets the purchasable entity's price.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The price, or NULL.
   */
  public function getPrice();

}
