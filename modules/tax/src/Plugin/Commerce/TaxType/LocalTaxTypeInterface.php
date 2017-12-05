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

}
