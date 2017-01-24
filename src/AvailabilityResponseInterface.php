<?php

namespace Drupal\commerce;

/**
 * Defines the interface for responses to availability checks.
 */
interface AvailabilityResponseInterface {

  /**
   * Checks whether this availability response indicates explicit availability.
   *
   * @return bool
   *   When TRUE then isUnavailable() and isNeutral() are FALSE.
   */
  public function isAvailable();

  /**
   * Checks whether this availability response indicates explicit unavailability.
   *
   * @return bool
   *   When TRUE then isAvailable() and isNeutral() are FALSE.
   */
  public function isUnavailable();

  /**
   * Checks whether this availability response indicates availability is not yet determined.
   *
   * @return bool
   *   When TRUE then isAvailable() and isUnavailable() are FALSE.
   */
  public function isNeutral();

  /**
   * Gets the minimum quantity available.
   *
   * @return int
   *   The minimum quantity available.
   */
  public function getMin();

  /**
   * Gets the maximum quantity available.
   *
   * @return int
   *   The maximum quantity available.
   */
  public function getMax();

  /**
   * Gets the reason for the response.
   *
   * @return string
   *   The reason for the response.
   */
  public function getReason();

}
