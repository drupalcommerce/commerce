<?php

namespace Drupal\commerce_promotion;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\commerce_order\Entity\OrderTypeInterface;
use Drupal\commerce_promotion\Entity\CouponInterface;
use Drupal\commerce_store\Entity\StoreInterface;

/**
 * Defines the promotion storage.
 */
class PromotionStorage extends CommerceContentEntityStorage implements PromotionStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadValid(OrderTypeInterface $order_type, StoreInterface $store) {
    $query = $this->buildLoadQuery($order_type, $store);
    // Only load promotions without coupons. Promotions with coupons are loaded
    // coupon-first in a different process.
    $query->notExists('coupons');
    $result = $query->execute();
    if (empty($result)) {
      return [];
    }
    $promotions = $this->loadMultiple($result);

    return $promotions;
  }

  /**
   * {@inheritdoc}
   */
  public function loadByCoupon(OrderTypeInterface $order_type, StoreInterface $store, CouponInterface $coupon) {
    $query = $this->buildLoadQuery($order_type, $store);
    $query->condition('coupons', $coupon->id());
    $result = $query->execute();
    if (empty($result)) {
      return [];
    }
    $promotions = $this->loadMultiple($result);

    return reset($promotions);

  }

  /**
   * Builds the base query for loading valid promotions.
   *
   * @param \Drupal\commerce_order\Entity\OrderTypeInterface $order_type
   *   The order type.
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The entity query.
   */
  protected function buildLoadQuery(OrderTypeInterface $order_type, StoreInterface $store) {
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
    return $query;
  }

}
