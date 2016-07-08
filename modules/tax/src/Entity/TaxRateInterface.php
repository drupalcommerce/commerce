<?php

namespace Drupal\commerce_tax\Entity;

use CommerceGuys\Tax\Model\TaxRateInterface as ExternalTaxRateInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for tax rates.
 *
 * The external tax rate interface contains getters, while this interface
 * adds matching setters. It also adds getters for referenced entity ids,
 * since the Drupal convention is to offer getters for both the id and the
 * entity itself. E.g getTypeId() / getType().
 *
 * @see \CommerceGuys\Tax\Model\Entity\TaxRateInterface
 */
interface TaxRateInterface extends ExternalTaxRateInterface, ConfigEntityInterface {

  /**
   * Gets the tax type ID.
   *
   * @return string
   *   The tax type ID.
   */
  public function getTypeId();

  /**
   * Sets the tax type.
   *
   * @param \Drupal\commerce_tax\Entity\TaxTypeInterface $type
   *   The tax type.
   */
  public function setType(TaxTypeInterface $type);

  /**
   * Sets the tax rate name.
   *
   * @param string $name
   *   The tax rate name.
   */
  public function setName($name);

  /**
   * Sets whether the tax rate is the default for its tax type.
   *
   * @param bool $default
   *   Whether the tax rate is the default.
   */
  public function setDefault($default);

  /**
   * Sets the tax rate amounts.
   *
   * @param \Drupal\commerce_tax\Entity\TaxRateAmountInterface[] $amounts
   *   The tax rate amounts.
   */
  public function setAmounts(array $amounts);

  /**
   * Adds a tax rate amount.
   *
   * @param \Drupal\commerce_tax\Entity\TaxRateAmountInterface $amount
   *   The tax rate amount.
   */
  public function addAmount(TaxRateAmountInterface $amount);

  /**
   * Removes a tax rate amount.
   *
   * @param \Drupal\commerce_tax\Entity\TaxRateAmountInterface $amount
   *   The tax rate amount.
   */
  public function removeAmount(TaxRateAmountInterface $amount);

  /**
   * Checks whether the tax rate has a tax rate amount.
   *
   * @param \Drupal\commerce_tax\Entity\TaxRateAmountInterface $amount
   *   The tax rate amount.
   *
   * @return bool
   *   TRUE if the tax rate amount was found, FALSE otherwise.
   */
  public function hasAmount(TaxRateAmountInterface $amount);

}
