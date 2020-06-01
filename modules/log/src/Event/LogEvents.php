<?php

namespace Drupal\commerce_log\Event;

/**
 * Defines events for the log module.
 */
final class LogEvents {

  /**
   * Name of the event fired to filter the changed fields used in the log diff.
   *
   * @Event
   *
   * @see \Drupal\commerce_log\Event\ProductVariationChangedFieldsFilterEvent
   */
  const PRODUCT_VARIATION_CHANGED_FIELDS_FILTER = 'commerce_log.product_variation.changed_fields_filter';

}
