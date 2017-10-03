<?php

namespace Drupal\commerce_price\Event;

use CommerceGuys\Intl\NumberFormat\NumberFormatEntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the number format event.
 *
 * @see \Drupal\commerce_price\Event\PriceEvents
 */
class NumberFormatEvent extends Event {

  /**
   * The number format.
   *
   * @var \CommerceGuys\Intl\NumberFormat\NumberFormatEntityInterface
   */
  protected $numberFormat;

  /**
   * Constructs a new NumberFormatEvent.
   *
   * @param \CommerceGuys\Intl\NumberFormat\NumberFormatEntityInterface $number_format
   *   The number format.
   */
  public function __construct(NumberFormatEntityInterface $number_format) {
    $this->numberFormat = $number_format;
  }

  /**
   * Gets the number format.
   *
   * @return \CommerceGuys\Intl\NumberFormat\NumberFormatEntityInterface
   *   The number format.
   */
  public function getNumberFormat() {
    return $this->numberFormat;
  }

}
