<?php

/**
 * @file
 * Contains \Drupal\commerce_order\LineItemStorage.
 */

namespace Drupal\commerce_order;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines the interface for line item storage.
 */
interface LineItemStorageInterface extends ContentEntityStorageInterface {

  /**
   * Constructs a new line item using the given purchasable entity.
   *
   * The new line item isn't saved.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\commerce_order\Entity\LineItemInterface
   *   The created line item.
   */
  public function createFromPurchasableEntity(PurchasableEntityInterface $entity, array $values = []);

}