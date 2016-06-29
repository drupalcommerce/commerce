<?php

namespace Drupal\commerce_product\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the ProductVariationAttribute constraint.
 */
class ProductVariationAttributeConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    // Get all attribute-values for this product_variation.
    // Do Drupal::entityQuery for the attributes-values.
    // If result:count == 0: ok
    // If result:count == 1 && product_variation->id() == self-->id(): ok
    // Else ->addViolation()
  }

  protected function testConflicts() {

    // get all attributes for this variation.
    $all_attributes = [];

    // load product with attribute combination with entity-field query
    // set error if a product is found that is not the current product.
  }

}
