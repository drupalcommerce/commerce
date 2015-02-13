<?php
/**
 * @file
 * Contains Drupal\commerce_tax\Plugin\CommerceTax\TaxTypeResolver\EuTaxTypeResolver.
 */

namespace Drupal\commerce_tax\Plugin\CommerceTax\TaxTypeResolver;

use CommerceGuys\Tax\Resolver\TaxType\CanadaTaxTypeResolver as BaseCanadaTaxTypeResolver;

/**
 * EU Tax Type Resolver.
 *
 * @TaxTypeResolver(
 *   id = "CA",
 * )
 */
class CanadaTaxTypeResolver extends BaseCanadaTaxTypeResolver {

}
