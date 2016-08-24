<?php

namespace Drupal\commerce_price;

use Drupal\commerce_price\Exception\CurrencyMismatchException;

/**
 * Provides a value object for monetary values.
 */
final class Price {

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
    Calculator::assertNumberFormat($amount);
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
   *   The string representation of the price.
   */
  public function __toString() {
    return $this->amount . ' ' . $this->currencyCode;
  }

  /**
   * Converts the current price to the given currency.
   *
   * @param string $currency_code
   *   The currency code.
   * @param string $rate
   *   A currency rate corresponding to the currency code.
   *
   * @return static
   *   The resulting price.
   */
  public function convert($currency_code, $rate = '1') {
    $new_amount = Calculator::multiply($this->amount, $rate);
    return new static($new_amount, $currency_code);
  }

  /**
   * Adds the given price to the current price.
   *
   * @param \Drupal\commerce_price\Price $price
   *   The price.
   *
   * @return static
   *   The resulting price.
   */
  public function add(Price $price) {
    $this->assertSameCurrency($this, $price);
    $new_amount = Calculator::add($this->amount, $price->getDecimalAmount());
    return new static($new_amount, $this->currencyCode);
  }

  /**
   * Subtracts the given price from the current price.
   *
   * @param \Drupal\commerce_price\Price $price
   *   The price.
   *
   * @return static
   *   The resulting price.
   */
  public function subtract(Price $price) {
    $this->assertSameCurrency($this, $price);
    $new_amount = Calculator::subtract($this->amount, $price->getDecimalAmount());
    return new static($new_amount, $this->currencyCode);
  }

  /**
   * Multiplies the current price by the given number.
   *
   * @param string $number
   *   The number.
   *
   * @return static
   *   The resulting price.
   */
  public function multiply($number) {
    $new_amount = Calculator::multiply($this->amount, $number);
    return new static($new_amount, $this->currencyCode);
  }

  /**
   * Divides the current price by the given number.
   *
   * @param string $number
   *   The number.
   *
   * @return static
   *   The resulting price.
   */
  public function divide($number) {
    $new_amount = Calculator::divide($this->amount, $number);
    return new static($new_amount, $this->currencyCode);
  }

  /**
   * Compares the current price with the given price.
   *
   * @param \Drupal\commerce_price\Price $price
   *   The price.
   *
   * @return int
   *   0 if both prices are equal, 1 if the first one is greater, -1 otherwise.
   */
  public function compareTo(Price $price) {
    $this->assertSameCurrency($this, $price);
    return Calculator::compare($this->amount, $price->getDecimalAmount());
  }

  /**
   * Gets whether the current price is zero.
   *
   * @return bool
   *   TRUE if the price is zero, FALSE otherwise.
   */
  public function isZero() {
    return Calculator::compare($this->amount, '0') == 0;
  }

  /**
   * Gets whether the current price is equivalent to the given price.
   *
   * @param \Drupal\commerce_price\Price $price
   *   The price.
   *
   * @return bool
   *   TRUE if the prices are equal, FALSE otherwise.
   */
  public function equals(Price $price) {
    return $this->compareTo($price) == 0;
  }

  /**
   * Gets whether the current price is greater than the given price.
   *
   * @param \Drupal\commerce_price\Price $price
   *   The price.
   *
   * @return bool
   *   TRUE if the current price is greater than the given price,
   *   FALSE otherwise.
   */
  public function greaterThan(Price $price) {
    return $this->compareTo($price) == 1;
  }

  /**
   * Gets whether the current price is greater than or equal to the given price.
   *
   * @param \Drupal\commerce_price\Price $price
   *   The price.
   *
   * @return bool
   *   TRUE if the current price is greater than or equal to the given price,
   *   FALSE otherwise.
   */
  public function greaterThanOrEqual(Price $price) {
    return $this->greaterThan($price) || $this->equals($price);
  }

  /**
   * Gets whether the current price is lesser than the given price.
   *
   * @param \Drupal\commerce_price\Price $price
   *   The price.
   *
   * @return bool
   *   TRUE if the current price is lesser than the given price,
   *   FALSE otherwise.
   */
  public function lessThan(Price $price) {
    return $this->compareTo($price) == -1;
  }

  /**
   * Gets whether the current price is lesser than or equal to the given price.
   *
   * @param \Drupal\commerce_price\Price $price
   *   The price.
   *
   * @return bool
   *   TRUE if the current price is lesser than or equal to the given price,
   *   FALSE otherwise.
   */
  public function lessThanOrEqual(Price $price) {
    return $this->lessThan($price) || $this->equals($price);
  }

  /**
   * Asserts that the two prices have the same currency.
   *
   * @param \Drupal\commerce_price\Price $first_price
   *   The first price.
   * @param \Drupal\commerce_price\Price $second_price
   *   The second price.
   *
   * @throws \Drupal\commerce_price\Exception\CurrencyMismatchException
   *   Thrown when the prices do not have the same currency.
   */
  protected function assertSameCurrency(Price $first_price, Price $second_price) {
    if ($first_price->getCurrencyCode() != $second_price->getCurrencyCode()) {
      throw new CurrencyMismatchException();
    }
  }

}
