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
    Calculator::assertAmountFormat($amount);
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
   * Convert a Price object to a different currency.
   *
   * @param string $currency
   *   A currency code.
   * @param string $rate
   *   A currency rate corresponding to the currency code.
   *
   * @return \Drupal\commerce_price\Price
   *   A new Price instance.
   */
  public function convert($currency, $rate = '1') {
    Calculator::assertAmountFormat($rate);
    $value = Calculator::multiply($this->amount, $rate);

    return $this->newPrice($value, $currency);
  }

  /**
   * Add a new price to the current price.
   *
   * @param \Drupal\commerce_price\Price $price
   *   A Price instance.
   *
   * @return \Drupal\commerce_price\Price
   *   A new Price instance.
   */
  public function add(Price $price) {
    $this->assertSameCurrency($this, $price);
    $value = Calculator::add($this->amount, $price->getDecimalAmount());

    return $this->newPrice($value);
  }

  /**
   * Subtract a new price from the current price.
   *
   * @param \Drupal\commerce_price\Price $price
   *   A Price instance.
   *
   * @return \Drupal\commerce_price\Price
   *   A new Price instance.
   */
  public function substract(Price $price) {
    $this->assertSameCurrency($this, $price);
    $value = Calculator::subtract($this->amount, $price->getDecimalAmount());

    return $this->newPrice($value);
  }

  /**
   * Multiply the current price by a given factor.
   *
   * @param string $factor
   *   A numeric string value.
   *
   * @return \Drupal\commerce_price\Price
   *   A new Price instance.
   */
  public function multiply($factor) {
    Calculator::assertAmountFormat($factor);
    $value = Calculator::multiply($this->amount, $factor);

    return $this->newPrice($value);
  }

  /**
   * Divide the current price by a given factor.
   *
   * @param string $factor
   *   A numeric string value.
   *
   * @return \Drupal\commerce_price\Price
   *   A new Price instance.
   */
  public function divide($factor) {
    Calculator::assertAmountFormat($factor);
    $value = Calculator::divide($this->amount, $factor);

    return $this->newPrice($value);
  }

  /**
   * Compare two prices with another.
   *
   * @param \Drupal\commerce_price\Price $other
   *   A Price object.
   *
   * @return int
   *   A value of 0, if both prices are equal, 1, if the current price is greater
   *   than the price being compared to and -1, if the current price is less than
   *   the price being compared to.
   */
  public function compareTo(Price $other) {
    $this->assertSameCurrency($this, $other);

    return Calculator::compare($this->amount, $other->getDecimalAmount());
  }

  /**
   * Check if this price is equivalent to another price.
   *
   * @param \Drupal\commerce_price\Price $other
   *   A Price object.
   *
   * @return bool
   *   TRUE if both prices are equal, FALSE otherwise.
   */
  public function equals(Price $other) {
    return $this->compareTo($other) == 0;
  }

  /**
   * Check if this price is greater than another price.
   *
   * @param \Drupal\commerce_price\Price $other
   *   A Price object.
   *
   * @return bool
   *   TRUE if this price is greater than price being compared to,
   *   FALSE otherwise.
   */
  public function greaterThan(Price $other) {
    return $this->compareTo($other) == 1;
  }

  /**
   * Check if this price is greater than or equal to another price.
   *
   * @param \Drupal\commerce_price\Price $other
   *   A Price object.
   *
   * @return bool
   *   TRUE if this price is greater than or equal to price being
   *   compared to, FALSE otherwise.
   */
  public function greaterThanOrEqual(Price $other) {
    return $this->greaterThan($other) || $this->equals($other);
  }

  /**
   * Check if this price is less than another price.
   *
   * @param \Drupal\commerce_price\Price $other
   *   A Price object.
   *
   * @return bool
   *   TRUE if this price is less than the price being compared to,
   *   FALSE otherwise.
   */
  public function lessThan(Price $other) {
    return $this->compareTo($other) == -1;
  }

  /**
   * Check if this price is less than or equal to another price.
   *
   * @param \Drupal\commerce_price\Price $other
   *   A Price object.
   *
   * @return bool
   *   TRUE if this price is less than or equal to price being
   *   compared to, FALSE otherwise.
   */
  public function lessThanOrEqual(Price $other) {
    return $this->lessThan($other) || $this->equals($other);
  }

  /**
   * Ensures that the two Price instances have the same currency.
   *
   * @param \Drupal\commerce_price\Price $operandA
   *   A Price object.
   * @param \Drupal\commerce_price\Price $operandB
   *   A Price object.
   *
   * @throws \Drupal\commerce_price\Exception\CurrencyMismatchException
   *   When the two prices have different currency codes.
   */
  protected function assertSameCurrency(Price $operandA, Price $operandB) {
    if ($operandA->getCurrencyCode() != $operandB->getCurrencyCode()) {
      throw new CurrencyMismatchException();
    }
  }

  /**
   * Creates a new Price instance using the provided amount.
   *
   * Used in calculation methods to store the result in a new instance.
   *
   * @param string $amount
   *   The decimal amount.
   * @param string $currency
   *   A currency code.
   *
   * @return \Drupal\commerce_price\Price
   *   A new Price instance.
   */
  protected function newPrice($amount, $currency = NULL) {

    if (strpos($amount, '.') != FALSE) {
      // The number is decimal, strip trailing zeroes.
      // If no digits remain after the decimal point, strip it as well.
      $amount = rtrim($amount, '0');
      $amount = rtrim($amount, '.');
    }
    $currency = $currency ?: $this->currencyCode;
    return new static($amount, $currency);
  }

}
