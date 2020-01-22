<?php

namespace Drupal\commerce_promotion\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Coupon valid reference constraint.
 *
 * Verifies that coupon is available and applies to the order.
 *
 * @Constraint(
 *   id = "CouponValid",
 *   label = @Translation("Coupon valid reference", context = "Validation")
 * )
 */
class CouponValidConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'The provided coupon code is invalid.';

}
