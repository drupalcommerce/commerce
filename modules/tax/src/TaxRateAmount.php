<?php

namespace Drupal\commerce_tax;

use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Represents a tax rate amount.
 */
class TaxRateAmount {

  /**
   * The amount.
   *
   * @var float
   */
  protected $amount;

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
   * Constructs a new TaxRateAmount instance.
   *
   * @param array $definition
   *   The definition.
   */
  public function __construct(array $definition) {
    foreach (['amount', 'start_date'] as $required_property) {
      if (!isset($definition[$required_property]) || $definition[$required_property] === '') {
        throw new \InvalidArgumentException(sprintf('Missing required property "%s".', $required_property));
      }
    }

    $this->amount = $definition['amount'];
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
      return t('@amount (@start_date - @end_date)', [
        '@amount' => $this->amount * 100 . '%',
        '@start_date' => $this->startDate->format('M jS Y'),
        '@end_date' => $this->endDate->format('M jS Y'),
      ]);
    }
    else {
      return t('@amount (Since @start_date)', [
        '@amount' => $this->amount * 100 . '%',
        '@start_date' => $this->startDate->format('M jS Y'),
        '@end_date' => $this->startDate->format('M jS Y'),
      ]);
    }
  }

  /**
   * Gets the amount.
   *
   * @return float
   *   The amount, expressed as a decimal.
   *   For example, 0.2 for a 20% tax rate.
   */
  public function getAmount() {
    return $this->amount;
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

}
