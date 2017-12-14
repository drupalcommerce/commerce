<?php

namespace Drupal\commerce_tax\Plugin\Commerce\TaxType;

/**
 * Defines the interface for local tax type plugins.
 *
 * Local tax types store one or more tax zones with their
 * corresponding tax rates.
 */
interface LocalTaxTypeInterface extends TaxTypeInterface {

  /**
   * Gets whether tax should be rounded at the order item level.
   *
   * @return bool
   *   TRUE if tax should be rounded at the order item level, FALSE otherwise.
   */
  public function shouldRound();

  /**
   * Gets the tax zones.
   *
   * @return \Drupal\commerce_tax\TaxZone[]
   *   The tax zones.
   */
  public function getZones();

  /**
   * Gets the countries outside of the zones whose people get taxed based
   * on additional agreements.
   */
  public function getExternalZones();

  /**
   * Gets the country codes of tax zone territories.
   *
   * @return String[]
   *   The country codes of all zone territories.
   */
  public function getZonesCountryCodes();

}
