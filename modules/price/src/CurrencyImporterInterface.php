<?php

/**
 * @file
 * Contains \Drupal\commerce_price\CurrencyImporterInterface.
 */

namespace Drupal\commerce_price;

/**
 * Defines the interface for currency importers.
 */
interface CurrencyImporterInterface {

  /**
   * Default language to fallback to.
   */
  const FALLBACK_LANGUAGE = 'en';

  /**
   * Gets all importable currencies.
   *
   * @param string $fallback
   *   The fallback language code.
   *
   * @return \Drupal\commerce_price\Entity\CurrencyInterface[]
   *    Array of importable currencies.
   */
  public function getImportableCurrencies($fallback = self::FALLBACK_LANGUAGE);

  /**
   * Creates a new currency object for the given currency code.
   *
   * @param string $currencyCode
   *   The currency code.
   *
   * @return \Drupal\commerce_price\Entity\Currency | bool
   *    The new currency or false if the currency is already imported.
   */
  public function importCurrency($currencyCode);

  /**
   * Imports translations for the currency entity.
   *
   * @param \Drupal\commerce_price\Entity\Currency[] $currencies
   *   Array of currencies to import translations for.
   * @param \Drupal\language\ConfigurableLanguageManagerInterface[] $languages
   *   Array of languages to import.
   *
   * @return \Drupal\commerce_price\Entity\Currency | bool
   *   The currency entity or false if the site is not multilingual.
   */
  public function importCurrencyTranslations($currencies = [], $languages = []);

}
