<?php

namespace Drupal\commerce_tax\Entity;

use CommerceGuys\Tax\Model\TaxRateAmountInterface as ExternalTaxRateAmountInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for tax rate amounts.
 *
 * The external tax rate amount interface contains getters, while this interface
 * adds matching setters. It also adds getters for referenced entity ids,
 * since the Drupal convention is to offer getters for both the id and the
 * entity itself. E.g getRateId() / getRate().
 *
 * @see \CommerceGuys\Tax\Model\TaxRateAmountInterface
 */
interface TaxRateAmountInterface extends ExternalTaxRateAmountInterface, ConfigEntityInterface {

  /**
   * Gets the tax rate ID.
   *
   * @return string
   *   The tax rate ID.
   */
  public function getRateId();

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
   * @param \DateTime $start_date
   *   The tax rate amount start date.
   */
  public function setStartDate(\DateTime $start_date);

  /**
   * Sets the tax rate amount end date.
   *
   * @param \DateTime $end_date
   *   The tax rate amount end date.
   */
  public function setEndDate(\DateTime $end_date);

}
