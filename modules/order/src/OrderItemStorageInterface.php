<?php

namespace Drupal\commerce_order;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines the interface for order item storage.
 */
interface OrderItemStorageInterface extends ContentEntityStorageInterface {

  /**
   * Constructs a new order item using the given purchasable entity.
   *
   * The new order item isn't saved.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface
   *   The created order item.
   */
  public function createFromPurchasableEntity(PurchasableEntityInterface $entity, array $values = []);

}
