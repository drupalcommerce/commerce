<?php

namespace Drupal\commerce_product\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Ensures product variation attribute uniqueness.
 *
 * @Constraint(
 *   id = "ProductVariationAttribute",
 *   label = @Translation("The attribute of the product variation.", context = "Validation")
 * )
 */
class ProductVariationAttributeConstraint extends Constraint {

  public $message = 'The attribute-combination is already in use and must be unique.';

}
