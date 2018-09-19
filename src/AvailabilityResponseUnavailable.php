<?php

namespace Drupal\commerce;

/**
 * An object representing a negative response to an availability check.
 */
class AvailabilityResponseUnavailable extends AvailabilityResponse {

  /**
   * Constructs a new AvailabilityResponseUnavailable object.
   *
   * @param int $min
   *   The minimum available.
   * @param int $max
   *   The maximum available.
   * @param string $reason
   *   The reason for unavailability.
   */
  public function __construct($min, $max, $reason) {
    $this->minimum = $min;
    $this->maximum = $max;
    $this->reason = $reason;
  }

  /**
   * {@inheritdoc}
   */
  public function isUnavailable() {
    return TRUE;
  }

}
