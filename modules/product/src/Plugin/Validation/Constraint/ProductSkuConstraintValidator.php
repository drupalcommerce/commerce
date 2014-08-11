<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\Validation\Constraint\ProductSkuConstraintValidator.
 */

namespace Drupal\commerce_product\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the ProductSku constraint.
 */
class ProductSkuConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($field_item, Constraint $constraint) {
    $sku = $field_item->value;
    if (isset($sku) && $sku !== '') {
      $sku_exists = (bool) \Drupal::entityQuery('commerce_product')
        ->condition("sku", $sku)
        ->range(0, 1)
        ->count()
        ->execute();
      if ($sku_exists) {
        $this->context->addViolation($constraint->message, array('%sku' => $sku));
      }
    }
  }

}
