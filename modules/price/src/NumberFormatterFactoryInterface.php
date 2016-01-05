<?php

/**
 * @file
 * Contains \Drupal\commerce_price\NumberFormatterFactoryInterface.
 */

namespace Drupal\commerce_price;

use CommerceGuys\Intl\Formatter\NumberFormatter;

/**
 * Defines the interface for NumberFormatter factories.
 */
interface NumberFormatterFactoryInterface {

  /**
   * Creates an instance of the number formatter for the current locale.
   *
   * @param int $style
   *   The format style, one of the NumberFormatter constants:
   *   DECIMAL, PERCENT, CURRENCY, CURRENCY_ACCOUNTING.
   *
   * @return \CommerceGuys\Intl\Formatter\NumberFormatterInterface
   *   The created number formatter.
   */
  public function createInstance($style = NumberFormatter::CURRENCY);

}
