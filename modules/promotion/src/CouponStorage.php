<?php

namespace Drupal\commerce_promotion;

use Drupal\commerce\CommerceContentEntityStorage;

/**
 * Defines the coupon storage.
 */
class CouponStorage extends CommerceContentEntityStorage implements CouponStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadByCode($code) {
    $coupons = $this->loadByProperties(['code' => $code, 'status' => TRUE]);

    return reset($coupons);
  }

}
