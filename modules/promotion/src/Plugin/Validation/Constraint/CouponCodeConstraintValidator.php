<?php

namespace Drupal\commerce_promotion\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the CouponCode constraint.
 */
class CouponCodeConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    $code = $items->first()->value;
    if (isset($code) && $code !== '') {
      $code_exists = (bool) \Drupal::entityQuery('commerce_promotion_coupon')
        ->condition('code', $code)
        ->condition('id', (int) $items->getEntity()->id(), '<>')
        ->range(0, 1)
        ->count()
        ->execute();

      if ($code_exists) {
        $this->context->buildViolation($constraint->message)
          ->setParameter('%code', $this->formatValue($code))
          ->addViolation();
      }
    }
  }

}
