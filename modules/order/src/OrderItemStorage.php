<?php

namespace Drupal\commerce_order;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\commerce\PurchasableEntityInterface;

/**
 * Defines the order item storage.
 */
class OrderItemStorage extends CommerceContentEntityStorage implements OrderItemStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function createFromPurchasableEntity(PurchasableEntityInterface $entity, array $values = []) {
    $values += [
      'type' => $entity->getOrderItemTypeId(),
      'title' => $entity->getOrderItemTitle(),
      'purchased_entity' => $entity,
      'unit_price' => $entity->getPrice(),
    ];
    return self::create($values);
  }

}
