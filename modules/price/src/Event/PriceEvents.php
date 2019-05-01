<?php

namespace Drupal\commerce_price\Event;

/**
 * Defines events for the price module.
 */
final class PriceEvents {

  /**
   * Name of the event fired when loading a number format.
   *
   * @Event
   *
   * @see \Drupal\commerce_price\Event\NumberFormatEvent
   *
   * @deprecated No longer fired. Subscribe to NUMBER_FORMAT instead.
   */
  const NUMBER_FORMAT_LOAD = 'commerce_price.number_format.load';

  /**
   * Name of the event fired when altering a number format.
   *
   * @Event
   *
   * @see \Drupal\commerce_price\Event\NumberFormatDefinitionEvent
   */
  const NUMBER_FORMAT = 'commerce_price.number_format';

}
