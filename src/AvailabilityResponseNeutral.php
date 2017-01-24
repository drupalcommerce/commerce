<?php

namespace Drupal\commerce;

/**
 * An object representing a negative response to an availability request.
 */
class AvailabilityResponseNeutral extends AvailabilityResponse {

  /**
   * {@inheritdoc}
   */
  public function isNeutral() {
    return TRUE;
  }

}
