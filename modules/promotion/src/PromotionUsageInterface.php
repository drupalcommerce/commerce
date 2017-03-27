<?php

namespace Drupal\commerce_promotion;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_promotion\Entity\CouponInterface;
use Drupal\commerce_promotion\Entity\PromotionInterface;

/**
 * Provides a way to check the usage of a promotion or coupon.
 */
interface PromotionUsageInterface {

  /**
   * Gets the usage for a promotion.
   *
   * @param \Drupal\commerce_promotion\Entity\PromotionInterface $promotion
   *   The promotion.
   * @param \Drupal\commerce_promotion\Entity\CouponInterface|null $coupon
   *   The promotion's coupon, optional.
   *
   * @return int
   *   The usage.
   */
  public function getUsage(PromotionInterface $promotion, CouponInterface $coupon = NULL);

  /**
   * Add a promotion usage record.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Drupal\commerce_promotion\Entity\PromotionInterface $promotion
   *   The promotion.
   * @param \Drupal\commerce_promotion\Entity\CouponInterface|null $coupon
   *   The promotion's coupon, optional.
   */
  public function addUsage(OrderInterface $order, PromotionInterface $promotion, CouponInterface $coupon = NULL);

}
