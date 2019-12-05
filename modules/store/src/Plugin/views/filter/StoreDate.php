<?php

namespace Drupal\commerce_store\Plugin\views\filter;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\datetime\Plugin\views\filter\Date;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Store date/time views filter.
 *
 * Used for filtering date/time values that are going to be used in
 * the store timezone, as opposed to the user's timezone.
 *
 * The "datetime" filter performs timezone conversion, assuming
 * that the entered value is in the user's timezone, and converting it to
 * UTC on storage. This filter ensures there is no conversion.
 *
 * @ViewsFilter("commerce_store_datetime")
 */
class StoreDate extends Date {

  /**
   * Constructs a new StoreDate handler.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack used to determine the current time.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DateFormatterInterface $date_formatter, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $date_formatter, $request_stack);

    $this->calculateOffset = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getTimezone() {
    return DateTimeItemInterface::STORAGE_TIMEZONE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getOffset($time, $timezone) {
    return 0;
  }

}
