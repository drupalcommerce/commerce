<?php

namespace Drupal\commerce_discount;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\commerce_order\Entity\OrderTypeInterface;
use Drupal\commerce_store\Entity\StoreInterface;

/**
 * Defines the discount storage.
 */
class DiscountStorage extends CommerceContentEntityStorage implements DiscountStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadValid(OrderTypeInterface $order_type, StoreInterface $store) {
    $query = $this->getQuery()
      ->condition('stores', [$store->id()], 'IN')
      ->condition('order_types', [$order_type->id()], 'IN')
      ->condition('start_date', gmdate('Y-m-d'), '<=')
      ->condition('end_date', gmdate('Y-m-d'), '>=')
      ->condition('status', TRUE);
    $result = $query->execute();
    if (empty($result)) {
      return [];
    }
    $discounts = $this->loadMultiple($result);

    return $discounts;
  }

}
