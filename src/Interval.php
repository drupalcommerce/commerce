<?php

namespace Drupal\commerce;

use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Provides a value object for intervals (1 month, 14 days, etc).
 */
final class Interval {

  /**
   * The number.
   *
   * @var string
   */
  protected $number;

  /**
   * The unit.
   *
   * @var string
   */
  protected $unit;

  /**
   * Constructs a new Interval object.
   *
   * @param string $number
   *   The number.
   * @param string $unit
   *   The unit.
   */
  public function __construct($number, $unit) {
    if (!is_numeric($number)) {
      throw new \InvalidArgumentException(sprintf('The provided interval number "%s" is not a numeric value.', $number));
    }
    if (!in_array($unit, ['hour', 'day', 'week', 'month', 'year'])) {
      throw new \InvalidArgumentException(sprintf('Invalid interval unit "%s" provided.', $unit));
    }

    $this->number = $number;
    $this->unit = $unit;
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
   * Gets the unit.
   *
   * @return string
   *   The unit.
   */
  public function getUnit() {
    return $this->unit;
  }

  /**
   * Gets the string representation of the interval.
   *
   * @return string
   *   The string representation of the interval.
   */
  public function __toString() {
    return $this->number . ' ' . $this->unit;
  }

  /**
   * Gets the array representation of the interval.
   *
   * @return array
   *   The array representation of the interval.
   */
  public function toArray() {
    return ['number' => $this->number, 'unit' => $this->unit];
  }

  /**
   * Adds the interval to the given date.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   The date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The new date.
   */
  public function add(DrupalDateTime $date) {
    /** @var \DateTime $new_date */
    $new_date = clone $date;
    $new_date->modify('+' . $this->__toString());
    // Jan 31st + 1 month should give Feb 28th, not Mar 3rd.
    if ($this->unit == 'month' && $new_date->format('d') !== $date->format('d')) {
      $new_date->modify('last day of previous month');
    }

    return $new_date;
  }

  /**
   * Subtracts the interval from the given date.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   The date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The new date.
   */
  public function subtract(DrupalDateTime $date) {
    /** @var \DateTime $new_date */
    $new_date = clone $date;
    $new_date->modify('-' . $this->__toString());
    // Mar 31st - 1 month should Feb 28th, not Mar 3rd.
    if ($this->unit == 'month' && $new_date->format('d') !== $date->format('d')) {
      $new_date->modify('last day of previous month');
    }

    return $new_date;
  }

  /**
   * Reduces the date to the lower boundary.
   *
   * For example, an Apr 14th date would be reduced to Apr 1st for monthly
   * intervals, and Jan 1st for yearly intervals.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   The date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The new date.
   */
  public function floor(DrupalDateTime $date) {
    /** @var \DateTime $new_date */
    $new_date = clone $date;
    switch ($this->unit) {
      case 'hour':
        $new_date->setTime($new_date->format('G'), 0);
        break;

      case 'day':
        $new_date->setTime(0, 0, 0);
        break;

      case 'week':
        // @todo Account for weeks that start on a sunday.
        $new_date->modify('monday this week');
        $new_date->setTime(0, 0, 0);
        break;

      case 'month':
        $new_date->modify('first day of this month');
        $new_date->setTime(0, 0, 0);
        break;

      case 'year':
        $new_date->modify('first day of january');
        $new_date->setTime(0, 0, 0);
        break;
    }

    return $new_date;
  }

  /**
   * Increases the date to the upper boundary.
   *
   * For example, an Apr 14th date would be increased to May 1st for a 1 month
   * interval, and to June 1st for a 2 month interval.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   The date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The new date.
   */
  public function ceil(DrupalDateTime $date) {
    return $this->add($this->floor($date));
  }

}
