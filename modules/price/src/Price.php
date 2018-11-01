<?php

namespace Drupal\commerce_price;

use Drupal\commerce_price\Exception\CurrencyMismatchException;

/**
 * Provides a value object for monetary values.
 *
 * Use the commerce_price.currency_formatter service to format prices.
 */
final class Price {

  /**
   * The number.
   *
   * @var string
   */
  protected $number;

  /**
   * The currency code.
   *
   * @var string
   */
  protected $currencyCode;

  /**
   * Constructs a new Price object.
   *
   * @param string $number
   *   The number.
   * @param string $currency_code
   *   The currency code.
   */
  public function __construct($number, $currency_code) {
    Calculator::assertNumberFormat($number);
    $this->assertCurrencyCodeFormat($currency_code);

    $this->number = (string) $number;
    $this->currencyCode = strtoupper($currency_code);
  }

  /**
   * Creates a new price from the given array.
   *
   * @param array $price
   *   The price array, with the "number" and "currency_code" keys.
   *
   * @return static
   */
  public static function fromArray(array $price) {
    if (!isset($price['number'], $price['currency_code'])) {
      throw new \InvalidArgumentException('Price::fromArray() called with a malformed array.');
    }
    return new static($price['number'], $price['currency_code']);
  }

  /**
   * Gets the number.
   *
   * @return string
   *   The number.
   */
  public function getNumber() {
    return $this->number;
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
    return Calculator::trim($this->number) . ' ' . $this->currencyCode;
  }

  /**
   * Gets the array representation of the price.
   *
   * @return array
   *   The array representation of the price.
   */
  public function toArray() {
    return ['number' => $this->number, 'currency_code' => $this->currencyCode];
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
    $new_number = Calculator::multiply($this->number, $rate);
    return new static($new_number, $currency_code);
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
    $new_number = Calculator::add($this->number, $price->getNumber());
    return new static($new_number, $this->currencyCode);
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
    $new_number = Calculator::subtract($this->number, $price->getNumber());
    return new static($new_number, $this->currencyCode);
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
    $new_number = Calculator::multiply($this->number, $number);
    return new static($new_number, $this->currencyCode);
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
    $new_number = Calculator::divide($this->number, $number);
    return new static($new_number, $this->currencyCode);
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
    return Calculator::compare($this->number, $price->getNumber());
  }

  /**
   * Gets whether the current price is positive.
   *
   * @return bool
   *   TRUE if the price is positive, FALSE otherwise.
   */
  public function isPositive() {
    return Calculator::compare($this->number, '0') == 1;
  }

  /**
   * Gets whether the current price is negative.
   *
   * @return bool
   *   TRUE if the price is negative, FALSE otherwise.
   */
  public function isNegative() {
    return Calculator::compare($this->number, '0') == -1;
  }

  /**
   * Gets whether the current price is zero.
   *
   * @return bool
   *   TRUE if the price is zero, FALSE otherwise.
   */
  public function isZero() {
    return Calculator::compare($this->number, '0') == 0;
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
   * Asserts that the currency code is in the right format.
   *
   * Serves only as a basic sanity check.
   *
   * @param string $currency_code
   *   The currency code.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the currency code is not in the right format.
   */
  protected function assertCurrencyCodeFormat($currency_code) {
    if (strlen($currency_code) != '3') {
      throw new \InvalidArgumentException();
    }
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
