<?php

namespace Drupal\commerce_price;

/**
 * Defines the interface for NumberFormatter factories.
 *
 * @deprecated Replaced by CurrencyFormatterInterface.
 */
interface NumberFormatterFactoryInterface {

  /**
   * Creates an instance of the number formatter for the current locale.
   *
   * @return \Drupal\commerce_price\LegacyNumberFormatter
   *   The created number formatter.
   */
  public function createInstance();

}
