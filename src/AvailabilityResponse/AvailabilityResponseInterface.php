<?php

namespace Drupal\commerce\AvailabilityResponse;

/**
 * Defines the interface for responses to availability checks.
 */
interface AvailabilityResponseInterface {

  /**
   * Gets the minimum quantity available for the given entity and context.
   *
   * @return int
   */
  public function getMin();

  /**
   * Gets the maximum quantity available for the given entity and context.
   *
   * @return int
   */
  public function getMax();

}
