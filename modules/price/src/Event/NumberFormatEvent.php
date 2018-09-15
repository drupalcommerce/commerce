<?php

namespace Drupal\commerce_price\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the number format event.
 *
 * @deprecated No longer fired, switch to NumberFormatDefinitionEvent.
 *
 * @see \Drupal\commerce_price\Event\PriceEvents
 */
class NumberFormatEvent extends Event {

  /**
   * The number format.
   *
   * @var object
   */
  protected $numberFormat;

  /**
   * Constructs a new NumberFormatEvent.
   *
   * @param object $number_format
   *   The number format.
   */
  public function __construct($number_format) {
    $this->numberFormat = $number_format;
  }

  /**
   * Gets the number format.
   *
   * @return object
   *   The number format.
   */
  public function getNumberFormat() {
    return $this->numberFormat;
  }

}
