<?php

/**
 * @file
 * Contains \Drupal\commerce_price\Entity\CurrencyInterface.
 */

namespace Drupal\commerce_price\Entity;

use CommerceGuys\Intl\Currency\CurrencyInterface as ExternalCurrencyInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for currencies.
 *
 * The external currency interface contains getters, while this interface
 * adds matching setters.
 *
 * @see \CommerceGuys\Intl\Currency\CurrencyInterface
 */
interface CurrencyInterface extends ExternalCurrencyInterface, ConfigEntityInterface {

  /**
   * Sets the alphabetic currency code.
   *
   * @param string $currency_code
   *   The alphabetic currency code.
   *
   * @return $this
   */
  public function setCurrencyCode($currency_code);

  /**
   * Sets the currency name.
   *
   * @param string $name
   *   The currency name.
   *
   * @return $this
   */
  public function setName($name);

  /**
   * Sets the numeric currency code.
   *
   * @param string $numeric_code
   *   The numeric currency code.
   *
   * @return $this
   */
  public function setNumericCode($numeric_code);

  /**
   * Sets the currency symbol.
   *
   * @param string $symbol
   *   The currency symbol.
   *
   * @return $this
   */
  public function setSymbol($symbol);

  /**
   * Sets the number of fraction digits.
   *
   * @param int $fraction_digits
   *   The number of fraction digits.
   *
   * @return $this
   */
  public function setFractionDigits($fraction_digits);

}
