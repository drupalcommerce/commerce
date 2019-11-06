<?php

namespace Drupal\commerce_tax\Resolver;

use Drupal\commerce_tax\Entity\TaxTypeInterface;

/**
 * Provides a trait for implementing TaxTypeAwareInterface.
 */
trait TaxTypeAwareTrait {

  /**
   * The tax type.
   *
   * @var \Drupal\commerce_tax\Entity\TaxTypeInterface
   */
  protected $taxType;

  /**
   * {@inheritdoc}
   */
  public function setTaxType(TaxTypeInterface $tax_type) {
    $this->taxType = $tax_type;
    return $this;
  }

}
