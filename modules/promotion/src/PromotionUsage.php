<?php

namespace Drupal\commerce_promotion;

use Drupal\commerce\EntityHelper;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_promotion\Entity\CouponInterface;
use Drupal\commerce_promotion\Entity\PromotionInterface;
use Drupal\Core\Database\Connection;

class PromotionUsage implements PromotionUsageInterface {

  /**
   * The database connection to use.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a PromotionUsage object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function register(OrderInterface $order, PromotionInterface $promotion, CouponInterface $coupon = NULL) {
    $this->connection->insert('commerce_promotion_usage')
      ->fields([
        'promotion_id' => $promotion->id(),
        'coupon_id' => $coupon ? $coupon->id() : 0,
        'order_id' => $order->id(),
        'mail' => $order->getEmail(),
      ])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $promotions) {
    $this->connection->delete('commerce_promotion_usage')
      ->condition('promotion_id', EntityHelper::extractIds($promotions), 'IN')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteByCoupon(array $coupons) {
    $this->connection->delete('commerce_promotion_usage')
      ->condition('coupon_id', EntityHelper::extractIds($coupons), 'IN')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function load(PromotionInterface $promotion, $mail = NULL) {
    $usages = $this->loadMultiple([$promotion], $mail);
    return $usages[$promotion->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function loadByCoupon(CouponInterface $coupon, $mail = NULL) {
    $usages = $this->loadMultipleByCoupon([$coupon], $mail);
    return $usages[$coupon->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $promotions, $mail = NULL) {
    if (empty($promotions)) {
      return [];
    }
    $promotion_ids = EntityHelper::extractIds($promotions);
    $query = $this->connection->select('commerce_promotion_usage', 'cpu');
    $query->addField('cpu', 'promotion_id');
    $query->addExpression('COUNT(promotion_id)', 'count');
    $query->condition('promotion_id', $promotion_ids, 'IN');
    if (!empty($mail)) {
      $query->condition('mail', $mail);
    }
    $query->groupBy('promotion_id');
    $result = $query->execute()->fetchAllAssoc('promotion_id', \PDO::FETCH_ASSOC);
    // Ensure that each promotion ID gets a count, even if it's not present
    // in the query due to non-existent usage.
    $counts = [];
    foreach ($promotion_ids as $promotion_id) {
      $counts[$promotion_id] = 0;
      if (isset($result[$promotion_id])) {
        $counts[$promotion_id] = $result[$promotion_id]['count'];
      }
    }

    return $counts;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultipleByCoupon(array $coupons, $mail = NULL) {
    if (empty($coupons)) {
      return [];
    }
    $coupon_ids = EntityHelper::extractIds($coupons);
    $query = $this->connection->select('commerce_promotion_usage', 'cpu');
    $query->addField('cpu', 'coupon_id');
    $query->addExpression('COUNT(coupon_id)', 'count');
    $query->condition('coupon_id', $coupon_ids, 'IN');
    if (!empty($mail)) {
      $query->condition('mail', $mail);
    }
    $query->groupBy('coupon_id');
    $result = $query->execute()->fetchAllAssoc('coupon_id', \PDO::FETCH_ASSOC);
    // Ensure that each coupon ID gets a count, even if it's not present
    // in the query due to non-existent usage.
    $counts = [];
    foreach ($coupon_ids as $coupon_id) {
      $counts[$coupon_id] = 0;
      if (isset($result[$coupon_id])) {
        $counts[$coupon_id] = $result[$coupon_id]['count'];
      }
    }

    return $counts;
  }

}
