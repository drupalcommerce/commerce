<?php

/**
 * @file
 * Contains \Drupal\commerce_price\CurrencyImporterInterface.
 */

namespace Drupal\commerce_price;

/**
 * Defines a currency importer.
 */
interface CurrencyImporterInterface {

  /**
   * Returns all importable currencies.
   *
   * @return Array
   *   Array of importable currencies.
   */
  public function getImportableCurrencies();

  /**
   * Creates a new currency object for the given currency code.
   *
   * @param string $currency_code
   *   The currency code.
   * @return \Drupal\commerce_price\Entity\CommerceCurrency|FALSE
   *   The new Currency or False if the currency is already imported.
   */
  public function importCurrency($currency_code);

}
