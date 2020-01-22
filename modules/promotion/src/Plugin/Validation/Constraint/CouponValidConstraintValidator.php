<?php

namespace Drupal\commerce_promotion\Plugin\Validation\Constraint;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_promotion\Entity\CouponInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the CouponValid constraint.
 */
class CouponValidConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    assert($value instanceof EntityReferenceFieldItemListInterface);
    $order = $value->getEntity();
    assert($order instanceof OrderInterface);
    // Only draft orders should be processed.
    if ($order->getState()->getId() !== 'draft') {
      return;
    }
    $coupons = $value->referencedEntities();
    foreach ($coupons as $delta => $coupon) {
      assert($coupon instanceof CouponInterface);
      if (!$coupon->available($order) || !$coupon->getPromotion()->applies($order)) {
        $this->context->buildViolation($constraint->message)
          ->atPath($delta . '.target_id')
          ->setInvalidValue($coupon->getCode())
          ->addViolation();
      }
    }
  }

}
