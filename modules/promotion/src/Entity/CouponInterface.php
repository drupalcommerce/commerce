<?php

namespace Drupal\commerce_promotion\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining coupon entities.
 */
interface CouponInterface extends ContentEntityInterface {

  /**
   * Gets the coupon code.
   *
   * @return string
   *   Code for the coupon.
   */
  public function getCode();

  /**
   * Sets the coupon code.
   *
   * @param string $code
   *   The coupon code.
   *
   * @return \Drupal\commerce_promotion\Entity\CouponInterface
   *   The coupon.
   */
  public function setCode($code);

  /**
   * Returns the coupon status indicator.
   *
   * @return bool
   *   TRUE if the coupon is active.
   */
  public function isActive();

  /**
   * Sets the status of a coupon.
   *
   * @param bool $active
   *   TRUE to make coupon active, FALSE to set it to inactive.
   *
   * @return \Drupal\commerce_promotion\Entity\CouponInterface
   *   The coupon.
   */
  public function setActive($active);

}
