<?php

namespace Drupal\commerce_promotion;

/**
 * Generates coupon codes (unique, machine readable identifiers for coupons).
 */
interface CouponCodeGeneratorInterface {

  /**
   * Validates the given pattern for the specified quantity.
   *
   * @param \Drupal\commerce_promotion\CouponCodePattern $pattern
   *   The pattern.
   * @param int $quantity
   *   The quantity.
   *
   * @return bool
   *   TRUE if the pattern is valid, FALSE otherwise.
   */
  public function validatePattern(CouponCodePattern $pattern, $quantity = 1);

  /**
   * Generates coupon codes.
   *
   * Ensures uniqueness, which means that depending on the pattern, the
   * number of generated codes might be smaller than requested.
   * This can be mitigated by using a pattern with a prefix/suffix.
   *
   * @param \Drupal\commerce_promotion\CouponCodePattern $pattern
   *   The pattern.
   * @param int $quantity
   *   The quantity.
   *
   * @return string[]
   *   The generated coupon codes.
   */
  public function generateCodes(CouponCodePattern $pattern, $quantity = 1);

}
