<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\Entity\TaxRateInterface.
 */

namespace Drupal\commerce_tax\Entity;

use CommerceGuys\Tax\Model\TaxRateInterface as ExternalTaxRateInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for tax rates.
 *
 * The external tax rate interface contains getters, while this interface adds
 * matching setters.
 *
 * @see \CommerceGuys\Tax\Model\Entity\TaxRateInterface
 */
interface TaxRateInterface extends ExternalTaxRateInterface, ConfigEntityInterface {

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
   * @param \Drupal\commerce_tax\Entity\TaxTypeInterface[] $amounts
   *   The tax rate amounts.
   */
  public function setAmounts($amounts);

  /**
   * Adds a tax rate amount.
   *
   * @param \Drupal\commerce_tax\Entity\TaxTypeInterface $amount
   *   The tax rate amount.
   */
  public function addAmount(TaxRateAmountInterface $amount);

  /**
   * Removes a tax rate amount.
   *
   * @param \Drupal\commerce_tax\Entity\TaxTypeInterface $amount
   *   The tax rate amount.
   */
  public function removeAmount(TaxRateAmountInterface $amount);

  /**
   * Checks whether the tax rate has a tax rate amount.
   *
   * @param \Drupal\commerce_tax\Entity\TaxTypeInterface $amount
   *   The tax rate amount.
   *
   * @return bool TRUE if the tax rate amount was found, FALSE otherwise.
   */
  public function hasAmount(TaxRateAmountInterface $amount);

}
