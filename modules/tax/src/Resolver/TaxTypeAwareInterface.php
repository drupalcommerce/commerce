<?php

namespace Drupal\commerce_tax\Resolver;

use Drupal\commerce_tax\Entity\TaxTypeInterface;

/**
 * Defines the interface for resolvers that depend on the tax type.
 *
 * The tax type is not passed to the resolver's resolve() method
 * because the method's signature couldn't be modified in 2.x
 * for backwards compatibility reasons.
 *
 * @see \Drupal\commerce_tax\Resolver\TaxTypeAwareTrait
 */
interface TaxTypeAwareInterface {

  /**
   * Sets the tax type.
   *
   * @param \Drupal\commerce_tax\Entity\TaxTypeInterface $tax_type
   *   The tax type.
   *
   * @return $this
   */
  public function setTaxType(TaxTypeInterface $tax_type);

}
