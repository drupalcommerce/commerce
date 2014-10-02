<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Plugin\Validation\Constraint\ProductSkuConstraintValidator.
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
  public function validate($items, Constraint $constraint) {
    $sku = $items->first()->value;
    if (isset($sku) && $sku !== '') {
      $product_id = $items->getEntity()->product_id->value;
      $sku_exists = (bool) \Drupal::entityQuery('commerce_product')
        ->condition("sku", $sku)
        ->condition('product_id', (int) $product_id, '<>')
        ->range(0, 1)
        ->count()
        ->execute();

      if ($sku_exists) {
        $this->context->addViolation($constraint->message, array('%sku' => $sku));
      }
    }
  }
}
