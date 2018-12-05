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
   * Registers usage for the given order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Drupal\commerce_promotion\Entity\PromotionInterface $promotion
   *   The promotion.
   * @param \Drupal\commerce_promotion\Entity\CouponInterface $coupon
   *   (Optional) The used coupon.
   */
  public function register(OrderInterface $order, PromotionInterface $promotion, CouponInterface $coupon = NULL);

  /**
   * Reassigns usage to a new customer email.
   *
   * @param string $old_mail
   *   The old customer email.
   * @param string $new_mail
   *   The new customer email.
   */
  public function reassign($old_mail, $new_mail);

  /**
   * Deletes all usage for the given promotions.
   *
   * @param \Drupal\commerce_promotion\Entity\PromotionInterface[] $promotions
   *   The promotions.
   */
  public function delete(array $promotions);

  /**
   * Deletes all usage for the given coupons.
   *
   * @param \Drupal\commerce_promotion\Entity\CouponInterface[] $coupons
   *   The coupons.
   */
  public function deleteByCoupon(array $coupons);

  /**
   * Loads the usage for the given promotion.
   *
   * The optional $mail parameter can be used to restrict the usage count
   * to a specific customer email.
   *
   * @param \Drupal\commerce_promotion\Entity\PromotionInterface $promotion
   *   The promotion.
   * @param string $mail
   *   (Optional) The customer email.
   *
   * @return int
   *   The usage.
   */
  public function load(PromotionInterface $promotion, $mail = NULL);

  /**
   * Loads the usage for the given coupon.
   *
   * The optional $mail parameter can be used to restrict the usage count
   * to a specific customer email.
   *
   * @param \Drupal\commerce_promotion\Entity\CouponInterface $coupon
   *   The coupon.
   * @param string $mail
   *   (Optional) The customer email.
   *
   * @return int
   *   The usage.
   */
  public function loadByCoupon(CouponInterface $coupon, $mail = NULL);

  /**
   * Loads the usage for the given promotions.
   *
   * The optional $mail parameter can be used to restrict the usage count
   * to a specific customer email.
   *
   * @param \Drupal\commerce_promotion\Entity\PromotionInterface[] $promotions
   *   The promotions.
   * @param string $mail
   *   (Optional) The customer email.
   *
   * @return array
   *   The usage counts, keyed by promotion ID.
   */
  public function loadMultiple(array $promotions, $mail = NULL);

  /**
   * Loads the usage for the given coupon.
   *
   * The optional $mail parameter can be used to restrict the usage count
   * to a specific customer email.
   *
   * @param \Drupal\commerce_promotion\Entity\CouponInterface[] $coupons
   *   The coupons.
   * @param string $mail
   *   (Optional) The customer email.
   *
   * @return array
   *   The usage counts, keyed by promotion ID.
   */
  public function loadMultipleByCoupon(array $coupons, $mail = NULL);

}
