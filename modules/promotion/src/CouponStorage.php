<?php

namespace Drupal\commerce_promotion;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\commerce_promotion\Entity\PromotionInterface;

/**
 * Defines the coupon storage.
 */
class CouponStorage extends CommerceContentEntityStorage implements CouponStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadEnabledByCode($code) {
    $coupons = $this->loadByProperties(['code' => $code, 'status' => TRUE]);
    return reset($coupons);
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultipleByPromotion(PromotionInterface $promotion) {
    return $this->loadByProperties(['promotion_id' => $promotion->id()]);
  }

}
