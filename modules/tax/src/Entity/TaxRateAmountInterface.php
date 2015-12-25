<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\Entity\TaxRateAmountInterface.
 */

namespace Drupal\commerce_tax\Entity;

use CommerceGuys\Tax\Model\TaxRateAmountInterface as ExternalTaxRateAmountInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for tax rate amounts.
 *
 * The external tax rate amount interface contains getters, while this
 * interface adds matching setters.
 *
 * @see \CommerceGuys\Tax\Model\TaxRateAmountInterface
 */
interface TaxRateAmountInterface extends ExternalTaxRateAmountInterface, ConfigEntityInterface {

  /**
   * Sets the tax rate.
   *
   * @param \Drupal\commerce_tax\Entity\TaxRateInterface $rate
   *   The tax rate.
   */
  public function setRate(TaxRateInterface $rate);

  /**
   * Sets the decimal tax rate amount.
   *
   * @param float $amount
   *   The tax rate amount expressed as a decimal.
   */
  public function setAmount($amount);

  /**
   * Sets the tax rate amount start date.
   *
   * @param \DateTime $startDate
   *   The tax rate amount start date.
   */
  public function setStartDate(\DateTime $startDate);

  /**
   * Sets the tax rate amount end date.
   *
   * @param \DateTime $endDate
   *   The tax rate amount end date.
   */
  public function setEndDate(\DateTime $endDate);

}
