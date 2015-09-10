<?php

/**
 * @file
 * Contains \Drupal\commerce_price\Event\NumberFormatEvent.
 */

namespace Drupal\commerce_price\Event;

use CommerceGuys\Intl\NumberFormat\NumberFormatEntityInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Defines the number format event.
 *
 * @see \Drupal\commerce_price\Event\PriceEvents
 */
class NumberFormatEvent extends GenericEvent {

  /**
   * The number format.
   *
   * @var \CommerceGuys\Intl\NumberFormat\NumberFormatEntityInterface
   */
  protected $numberFormat;

  /**
   * Constructs a new NumberFormatEvent.
   *
   * @param \CommerceGuys\Intl\NumberFormat\NumberFormatEntityInterface $numberFormat
   *   The number format.
   */
  public function __construct(NumberFormatEntityInterface $numberFormat) {
    $this->numberFormat = $numberFormat;
  }

  /**
   * The number format the event refers to.
   *
   * @return \CommerceGuys\Intl\NumberFormat\NumberFormatEntityInterface
   */
  public function getNumberFormat() {
    return $this->numberFormat;
  }

}
