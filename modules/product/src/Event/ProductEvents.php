<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Event\ProductEvents.
 */

namespace Drupal\commerce_product\Event;

final class ProductEvents {

  /**
   * Name of the event fired when filtering variations.
   *
   * @Event
   *
   * @see \Drupal\commerce_product\Event\FilterVariationsEvent
   */
  const FILTER_VARIATIONS = "commerce_product.filter_variations";

}
