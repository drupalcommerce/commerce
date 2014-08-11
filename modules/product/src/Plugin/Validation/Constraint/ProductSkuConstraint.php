<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Plugin\Validation\Constraint\ProductSkuConstraint.
 */

namespace Drupal\commerce_product\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Supports validating comment author names.
 *
 * @Plugin(
 *   id = "ProductSku",
 *   label = @Translation("The SKU of the product.", context = "Validation")
 * )
 */
class ProductSkuConstraint extends Constraint {

  public $message = '%sku belongs to a product.';

}
