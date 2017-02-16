<?php

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
   * @param string $currency_code
   *   The currency code.
   *
   * @return \Drupal\commerce_price\Entity\CurrencyInterface
   *   The saved currency entity.
   *
   * @throws \CommerceGuys\Intl\Exception\UnknownCurrencyException
   *   Thrown when the currency couldn't be found in the library definitions.
   */
  public function import($currency_code);

  /**
   * Imports currency data for the given country code.
   *
   * @param string $country_code
   *   The country code.
   *
   * @return \Drupal\commerce_price\Entity\CurrencyInterface|null
   *   The saved currency entity or NULL if the given country's currency
   *   isn't known.
   *
   * @throws \CommerceGuys\Intl\Exception\UnknownCountryException
   *   Thrown when the country couldn't be found in the library definitions.
   */
  public function importByCountry($country_code);

  /**
   * Imports translations for the given language codes.
   *
   * @param array $langcodes
   *   Array of language codes to import translations for.
   */
  public function importTranslations(array $langcodes);

}
