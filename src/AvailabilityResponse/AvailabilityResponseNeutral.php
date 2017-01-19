<?php

namespace Drupal\commerce\AvailabilityResponse;

class AvailabilityResponseNeutral extends AvailabilityResponse {

  /**
   * {@inheritdoc}
   */
  public function isNeutral() {
    return TRUE;
  }

}
