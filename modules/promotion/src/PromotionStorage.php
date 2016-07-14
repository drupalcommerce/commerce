<?php

namespace Drupal\commerce_promotion;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\commerce_order\Entity\OrderTypeInterface;
use Drupal\commerce_store\Entity\StoreInterface;

/**
 * Defines the promotion storage.
 */
class PromotionStorage extends CommerceContentEntityStorage implements PromotionStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadValid(OrderTypeInterface $order_type, StoreInterface $store) {
    $query = $this->getQuery();

    $or_condition = $query->orConditionGroup()
      ->condition('end_date', gmdate('Y-m-d'), '>=')
      ->notExists('end_date', gmdate('Y-m-d'));
    $query
      ->condition('stores', [$store->id()], 'IN')
      ->condition('order_types', [$order_type->id()], 'IN')
      ->condition('start_date', gmdate('Y-m-d'), '<=')
      ->condition('status', TRUE)
      ->condition($or_condition);
    $result = $query->execute();
    if (empty($result)) {
      return [];
    }
    $promotions = $this->loadMultiple($result);

    return $promotions;
  }

}
