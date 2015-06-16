<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Plugin\Validation\Constraint\ProductSkuConstraint.
 */

namespace Drupal\commerce_product\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Supports validating product skus.
 *
 * @Constraint(
 *   id = "ProductSku",
 *   label = @Translation("The SKU of the product.", context = "Validation")
 * )
 */
class ProductSkuConstraint extends Constraint {

  public $message = 'The SKU %sku is already in use and must be unique. Please supply another value.';

}
