<?php

namespace Drupal\commerce_price\Event;

/**
 * Defines events for the price module.
 */
final class PriceEvents {

  /**
   * Name of the event fired when loading a number format.
   *
   * This event allows modules to alter the loaded number format before it's
   * returned and used by the system. The event listener method receives a
   * \Drupal\commerce_price\Event\NumberFormatEvent instance.
   *
   * @Event
   *
   * @see \Drupal\commerce_price\Event\NumberFormatEvent
   */
  const NUMBER_FORMAT_LOAD = 'commerce_price.number_format.load';

}
