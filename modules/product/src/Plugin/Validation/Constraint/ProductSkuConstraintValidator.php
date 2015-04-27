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
      $productId = $items->getEntity()->product_id->value;
      $storeId = $items->getEntity()->getStore()->id();
      $skuExists = (bool) \Drupal::entityQuery('commerce_product')
        ->condition("sku", $sku)
        ->condition('product_id', (int) $productId, '<>')
        ->condition('store_id', $storeId)
        ->range(0, 1)
        ->count()
        ->execute();

      if ($skuExists) {
        $this->context->addViolation($constraint->message, ['%sku' => $sku]);
      }
    }
  }
}
