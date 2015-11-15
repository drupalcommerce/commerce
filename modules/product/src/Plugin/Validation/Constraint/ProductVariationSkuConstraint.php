<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Plugin\Validation\Constraint\ProductVariationSkuConstraint.
 */

namespace Drupal\commerce_product\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Ensures product variation SKU uniqueness.
 *
 * @Constraint(
 *   id = "ProductVariationSku",
 *   label = @Translation("The SKU of the product variation.", context = "Validation")
 * )
 */
class ProductVariationSkuConstraint extends Constraint {

  public $message = 'The SKU %sku is already in use and must be unique.';

}
