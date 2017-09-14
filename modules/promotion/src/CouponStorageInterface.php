<?php

namespace Drupal\commerce_promotion;

use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines the interface for coupon storage.
 */
interface CouponStorageInterface extends ContentEntityStorageInterface {

  /**
   * Loads the coupon for the given coupon code.
   *
   * @param string $code
   *   The coupon code.
   *
   * @return \Drupal\commerce_promotion\Entity\CouponInterface
   *   The coupon.
   */
  public function loadByCode($code);

}
