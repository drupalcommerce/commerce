<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\Entity\TaxTypeInterface.
 */

namespace Drupal\commerce_tax\Entity;

use CommerceGuys\Tax\Model\TaxTypeInterface as ExternalTaxTypeInterface;
use Drupal\address\ZoneInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for tax types.
 *
 * The external tax type interface contains getters, while this interface adds
 * matching setters.
 *
 * @see \CommerceGuys\Tax\Model\TaxTypeInterface
 */
interface TaxTypeInterface extends ExternalTaxTypeInterface, ConfigEntityInterface {

  /**
   * Sets the tax type name.
   *
   * @param string $name
   *   The tax type name.
   */
  public function setName($name);

  /**
   * Sets the tax type generic label.
   *
   * @param string $genericLabel
   *   The tax type generic label.
   */
  public function setGenericLabel($genericLabel);

  /**
   * Sets whether the tax type is compound.
   *
   * @param bool $compound
   *   Whether the tax type is compound.
   */
  public function setCompound($compound);

  /**
   * Sets whether the tax type is display inclusive.
   *
   * @param bool $displayInclusive
   *   Whether the tax type is display inclusive.
   */
  public function setDisplayInclusive($displayInclusive);

  /**
   * Sets the tax type rounding mode.
   *
   * @param int $roundingMode
   *   The tax type rounding mode, a ROUND_ constant.
   */
  public function setRoundingMode($roundingMode);

  /**
   * Sets the tax type zone.
   *
   * @param \Drupal\address\ZoneInterface $zone
   *   The tax type zone.
   */
  public function setZone(ZoneInterface $zone);

  /**
   * Sets the tax type tag.
   *
   * @param string $tag The tax type tag.
   */
  public function setTag($tag);

  /**
   * Sets the tax rates.
   *
   * @param \Drupal\commerce_tax\Entity\TaxRateInterface[] $rates
   *   The tax rates.
   */
  public function setRates($rates);

  /**
   * Adds a tax rate.
   *
   * @param \Drupal\commerce_tax\Entity\TaxRateInterface $rate
   *   The tax rate.
   */
  public function addRate(TaxRateInterface $rate);

  /**
   * Removes a tax rate.
   *
   * @param \Drupal\commerce_tax\Entity\TaxRateInterface $rate
   *   The tax rate.
   */
  public function removeRate(TaxRateInterface $rate);

  /**
   * Checks whether the tax type has a tax rate.
   *
   * @param \Drupal\commerce_tax\Entity\TaxRateInterface $rate
   *   The tax rate.
   *
   * @return bool
   *   TRUE if the tax rate was found, FALSE otherwise.
   */
  public function hasRate(TaxRateInterface $rate);

}
