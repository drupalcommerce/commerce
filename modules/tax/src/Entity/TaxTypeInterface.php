<?php

namespace Drupal\commerce_tax\Entity;

use CommerceGuys\Tax\Model\TaxTypeInterface as ExternalTaxTypeInterface;
use Drupal\address\Entity\ZoneInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for tax types.
 *
 * The external tax type interface contains getters, while this interface
 * adds matching setters. It also adds getters for referenced entity ids,
 * since the Drupal convention is to offer getters for both the id and the
 * entity itself. E.g getZoneId() / getZone().
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
   * @param string $generic_label
   *   The tax type generic label.
   */
  public function setGenericLabel($generic_label);

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
   * @param bool $display_inclusive
   *   Whether the tax type is display inclusive.
   */
  public function setDisplayInclusive($display_inclusive);

  /**
   * Sets the tax type rounding mode.
   *
   * @param int $rounding_mode
   *   The tax type rounding mode, a ROUND_ constant.
   */
  public function setRoundingMode($rounding_mode);

  /**
   * Gets the tax type zone ID.
   *
   * @return string
   *   The ID of the tax type zone.
   */
  public function getZoneId();

  /**
   * Sets the tax type zone.
   *
   * @param \Drupal\address\Entity\ZoneInterface $zone
   *   The tax type zone.
   */
  public function setZone(ZoneInterface $zone);

  /**
   * Sets the tax type tag.
   *
   * @param string $tag
   *   The tax type tag.
   */
  public function setTag($tag);

  /**
   * Sets the tax rates.
   *
   * @param \Drupal\commerce_tax\Entity\TaxRateInterface[] $rates
   *   The tax rates.
   */
  public function setRates(array $rates);

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
