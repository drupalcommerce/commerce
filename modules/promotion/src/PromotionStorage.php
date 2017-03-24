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

    $usages_query = $this->database->select('commerce_promotion_usage', 'cpu');
    $usages_query->condition('promotion_id', array_keys($result), 'IN');
    $usages_query->addField('cpu', 'promotion_id');
    $usages_query->addExpression("COUNT(promotion_id)", 'count');
    $usages_query->groupBy('promotion_id');
    $usages_result = $usages_query->execute()->fetchAllAssoc('promotion_id', \PDO::FETCH_ASSOC);

    /** @var \Drupal\commerce_promotion\Entity\PromotionInterface[] $promotions */
    $promotions = $this->loadMultiple($result);

    // Remove any promotions that have hit their usage limit.
    foreach ($promotions as $promotion) {
      $promotion_id = $promotion->id();
      if (isset($usages_result[$promotion_id])) {
        if ($promotion->getUsageLimit() >= $usages_result[$promotion_id]['count']) {
          unset($promotions[$promotion_id]);
        }
      }
    }

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
    $query->sort('weight', 'ASC');
    return $query;
  }

}
