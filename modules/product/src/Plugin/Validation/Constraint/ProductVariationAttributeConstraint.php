<?php

namespace Drupal\commerce_product\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Ensures product variation attribute uniqueness.
 *
 * @Constraint(
 *   id = "ProductVariationAttribute",
 *   label = @Translation("The attribute of the product variation.", context = "Validation"),
 *   type = "entity:commerce_product_variation"
 * )
 */
class ProductVariationAttributeConstraint extends CompositeConstraintBase {

  public $message = 'The attribute-combination is already in use and must be unique.';

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    // We lookup the attribute fields when validating.
    return [];
  }

}
