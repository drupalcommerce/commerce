<?php

namespace Drupal\commerce_promotion;

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
  public function getUsage(PromotionInterface $promotion, CouponInterface $coupon = NULL) {
    $query = $this->connection->select('commerce_promotion_usage', 'cpu');
    $query->condition('promotion_id', $promotion->id());

    if ($coupon) {
      $query->condition('coupon_id', $coupon->id());
    }

    return $query->countQuery()->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function addUsage(OrderInterface $order, PromotionInterface $promotion, CouponInterface $coupon = NULL) {
    $this->connection->merge('commerce_promotion_usage')
      ->key([
        'promotion_id' => $promotion->id(),
        'order_id' => $order->id(),
        'uid' => $order->getCustomerId(),
      ])
      ->fields([
        'promotion_id' => $promotion->id(),
        'coupon_id' => ($coupon) ? $coupon->id() : 0,
        'order_id' => $order->id(),
        'uid' => $order->getCustomerId(),
      ])
      ->execute();
  }

}
