<?php

namespace Drupal\commerce_promotion;

use Drupal\commerce_promotion\Entity\PromotionInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines the interface for coupon storage.
 */
interface CouponStorageInterface extends ContentEntityStorageInterface {

  /**
   * Loads the enabled coupon for the given coupon code.
   *
   * @param string $code
   *   The coupon code.
   *
   * @return \Drupal\commerce_promotion\Entity\CouponInterface
   *   The coupon.
   */
  public function loadEnabledByCode($code);

  /**
   * Loads all coupons for the given promotion.
   *
   * @param \Drupal\commerce_promotion\Entity\PromotionInterface $promotion
   *   The promotion.
   *
   * @return \Drupal\commerce_promotion\Entity\CouponInterface[]
   *   The coupons.
   */
  public function loadMultipleByPromotion(PromotionInterface $promotion);

}
