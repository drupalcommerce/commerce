<?php

/**
 * @file
 * Contains \Drupal\commerce_price\CurrencyImporterInterface.
 */

namespace Drupal\commerce_price;

/**
 * Imports the library-provided currency data into config entities.
 */
interface CurrencyImporterInterface {

  /**
   * Gets a list of importable currencies.
   *
   * @return array
   *   An array in the currencyCode => name format.
   */
  public function getImportable();

  /**
   * Imports currency data for the given currency code.
   *
   * @param string $currencyCode
   *   The currency code.
   *
   * @return \Drupal\commerce_price\Entity\CurrencyInterface
   *   The saved currency entity.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the currency already exists in the system.
   * @throws \CommerceGuys\Intl\Exception\UnknownCurrencyException
   *   Thrown when the currency couldn't be found in the library definitions.
   */
  public function import($currencyCode);

  /**
   * Imports translations for the given language codes.
   *
   * @param array $langcodes
   *   Array of language codes to import translations for.
   */
  public function importTranslations(array $langcodes);

}
