<?php

namespace Drupal\commerce_promotion;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_promotion\Entity\CouponInterface;
use Drupal\commerce_promotion\Entity\PromotionInterface;

/**
 * Tracks promotion usage.
 *
 * The customer is tracked by storing the email, which allows tracking
 * both authenticated and anonymous customers the same way.
 */
interface PromotionUsageInterface {

  /**
   * Add a promotion usage record.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Drupal\commerce_promotion\Entity\PromotionInterface $promotion
   *   The promotion.
   * @param \Drupal\commerce_promotion\Entity\CouponInterface $coupon
   *   (Optional) The used coupon.
   */
  public function addUsage(OrderInterface $order, PromotionInterface $promotion, CouponInterface $coupon = NULL);

  /**
   * Deletes all usage records for the given promotions.
   *
   * @param \Drupal\commerce_promotion\Entity\PromotionInterface[] $promotions
   *   The promotions.
   */
  public function deleteUsage(array $promotions);

  /**
   * Gets the usage for the given promotion.
   *
   * The optional $coupon and $mail parameters can be used to restrict the
   * usage count to only the provided coupon / customer email.
   *
   * @param \Drupal\commerce_promotion\Entity\PromotionInterface $promotion
   *   The promotion.
   * @param \Drupal\commerce_promotion\Entity\CouponInterface $coupon
   *   (Optional) The coupon.
   * @param string $mail
   *   (Optional) The customer email.
   *
   * @return int
   *   The usage.
   */
  public function getUsage(PromotionInterface $promotion, CouponInterface $coupon = NULL, $mail = NULL);

  /**
   * Gets the usage for the given promotions.
   *
   * The optional $coupons and $mail parameters can be used to restrict the
   * usage count to only the provided coupons / customer email.
   *
   * @param \Drupal\commerce_promotion\Entity\PromotionInterface[] $promotions
   *   The promotions.
   * @param \Drupal\commerce_promotion\Entity\CouponInterface[] $coupons
   *   (Optional) The coupons.
   * @param string $mail
   *   (Optional) The customer email.
   *
   * @return array
   *   The usage counts, keyed by promotion ID.
   */
  public function getUsageMultiple(array $promotions, array $coupons = [], $mail = NULL);

}
