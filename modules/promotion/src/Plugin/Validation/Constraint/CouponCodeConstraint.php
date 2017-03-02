<?php

namespace Drupal\commerce_promotion\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Ensures promotion coupon code uniqueness.
 *
 * @Constraint(
 *   id = "CouponCode",
 *   label = @Translation("The code of the promotion coupon.", context = "Validation")
 * )
 */
class CouponCodeConstraint extends Constraint {

  public $message = 'The Code %code is already in use and must be unique.';

}
