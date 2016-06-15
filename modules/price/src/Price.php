<?php

namespace Drupal\commerce_price;

/**
 * Provides a value object for monetary values.
 */
class Price {

  /**
   * The decimal amount.
   *
   * @var string
   */
  protected $amount;

  /**
   * The currency code.
   *
   * @var string
   */
  protected $currencyCode;

  /**
   * Constructs a new Price object.
   *
   * @param string $amount
   *   The decimal amount.
   * @param string $currency_code
   *   The currency code.
   */
  public function __construct($amount, $currency_code) {
    $this->amount = (string) $amount;
    $this->currencyCode = strtoupper($currency_code);
  }

  /**
   * Gets the decimal amount.
   *
   * @return string
   *   The decimal amount.
   */
  public function getDecimalAmount() {
    return $this->amount;
  }

  /**
   * Gets the currency code.
   *
   * @return string
   *   The currency code.
   */
  public function getCurrencyCode() {
    return $this->currencyCode;
  }

  /**
   * Gets the string representation of the price.
   *
   * @return string
   */
  public function __toString() {
    return $this->amount . ' ' . $this->currencyCode;
  }

}
