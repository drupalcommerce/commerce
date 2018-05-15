<?php

namespace Drupal\commerce_price\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for currencies.
 */
interface CurrencyInterface extends ConfigEntityInterface {

  /**
   * Gets the alphabetic currency code.
   *
   * @return string
   *   The alphabetic currency code.
   */
  public function getCurrencyCode();

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
   * Gets the currency name.
   *
   * @return string
   *   The currency name.
   */
  public function getName();

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
   * Gets the numeric currency code.
   *
   * The numeric code has three digits, and the first one can be a zero,
   * hence the need to pass it around as a string.
   *
   * @return string
   *   The numeric currency code.
   */
  public function getNumericCode();

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
   * Gets the currency symbol.
   *
   * @return string
   *   The currency symbol.
   */
  public function getSymbol();

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
   * Gets the number of fraction digits.
   *
   * Used when rounding or formatting an amount for display.
   *
   * @return int
   *   The number of fraction digits.
   */
  public function getFractionDigits();

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
