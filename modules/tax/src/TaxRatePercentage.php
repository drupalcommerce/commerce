<?php

namespace Drupal\commerce_tax;

use Drupal\commerce_price\Calculator;
use Drupal\commerce_price\Price;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Represents a tax rate percentage.
 */
class TaxRatePercentage {

  /**
   * The number.
   *
   * @var string
   */
  protected $number;

  /**
   * The start date.
   *
   * @var \Drupal\Core\Datetime\DrupalDateTime
   */
  protected $startDate;

  /**
   * The end date.
   *
   * @var \Drupal\Core\Datetime\DrupalDateTime
   */
  protected $endDate;

  /**
   * Constructs a new TaxRatePercentage instance.
   *
   * @param array $definition
   *   The definition.
   */
  public function __construct(array $definition) {
    foreach (['number', 'start_date'] as $required_property) {
      if (!isset($definition[$required_property]) || $definition[$required_property] === '') {
        throw new \InvalidArgumentException(sprintf('Missing required property "%s".', $required_property));
      }
    }
    if (is_float($definition['number'])) {
      throw new \InvalidArgumentException(sprintf('The provided number "%s" must be a string, not a float.', $definition['number']));
    }
    if (!is_numeric($definition['number'])) {
      throw new \InvalidArgumentException(sprintf('The provided number "%s" is not a numeric value.', $definition['number']));
    }

    $this->number = $definition['number'];
    $this->startDate = new DrupalDateTime($definition['start_date']);
    $this->endDate = !empty($definition['end_date']) ? new DrupalDateTime($definition['end_date']) : NULL;
  }

  /**
   * Gets the string representation of the object.
   *
   * @return string
   *   The string representation of the object.
   */
  public function toString() {
    if ($this->endDate) {
      return t('@number (@start_date - @end_date)', [
        '@number' => $this->number * 100 . '%',
        '@start_date' => $this->startDate->format('M jS Y'),
        '@end_date' => $this->endDate->format('M jS Y'),
      ]);
    }
    else {
      return t('@number (Since @start_date)', [
        '@number' => $this->number * 100 . '%',
        '@start_date' => $this->startDate->format('M jS Y'),
        '@end_date' => $this->startDate->format('M jS Y'),
      ]);
    }
  }

  /**
   * Gets the number.
   *
   * @return string
   *   The number, expressed as a decimal.
   *   For example, 0.2 for a 20% tax rate.
   */
  public function getNumber() {
    return $this->number;
  }

  /**
   * Gets the start date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The start date.
   */
  public function getStartDate() {
    return $this->startDate;
  }

  /**
   * Gets the end date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   The end date, or NULL if not known.
   */
  public function getEndDate() {
    return $this->endDate;
  }

  /**
   * Calculates the tax amount for the given price.
   *
   * @param \Drupal\commerce_price\Price $price
   *   The price.
   * @param bool $included
   *   Whether tax is already included in the price.
   *
   * @return \Drupal\commerce_price\Price
   *   The unrounded tax amount.
   */
  public function calculateTaxAmount(Price $price, $included = FALSE) {
    $tax_amount = $price->multiply($this->number);
    if ($included) {
      $divisor = Calculator::add('1', $this->number);
      $tax_amount = $tax_amount->divide($divisor);
    }
    return $tax_amount;
  }

}
