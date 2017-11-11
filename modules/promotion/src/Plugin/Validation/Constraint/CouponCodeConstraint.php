<?php

namespace Drupal\commerce_promotion\Plugin\Validation\Constraint;

use Drupal\Core\Validation\Plugin\Validation\Constraint\UniqueFieldConstraint;

/**
 * Ensures coupon code uniqueness.
 *
 * @Constraint(
 *   id = "CouponCode",
 *   label = @Translation("Coupon code", context = "Validation")
 * )
 */
class CouponCodeConstraint extends UniqueFieldConstraint {

  public $message = 'The coupon code %value is already in use and must be unique.';

}
