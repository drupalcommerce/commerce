<?php

namespace Drupal\commerce_order;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\commerce\PurchasableEntityInterface;

/**
 * Defines the line item storage.
 */
class LineItemStorage extends CommerceContentEntityStorage implements LineItemStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function createFromPurchasableEntity(PurchasableEntityInterface $entity, array $values = []) {
    $values += [
      'type' => $entity->getLineItemTypeId(),
      'title' => $entity->getLineItemTitle(),
      'purchased_entity' => $entity,
      'unit_price' => $entity->getPrice(),
    ];
    return self::create($values);
  }

}
