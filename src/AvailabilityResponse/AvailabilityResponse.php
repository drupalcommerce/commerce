<?php

namespace Drupal\commerce\AvailabilityResponse;

/**
 * An object representing a response to an availability request.
 */
abstract class AvailabilityResponse implements AvailabilityResponseInterface {

  /**
   * The minimum quantity available.
   *
   * @var int
   */
  protected $minimum = 0;

  /**
   * The maximum quantity available.
   *
   * @var int
   */
  protected $maximum = 0;

  /**
   * The reason for the result.
   *
   * @var string
   */
  protected $reason = '';

  /**
   * Gets the minimum quantity available.
   *
   * @return int
   *   The minimum quantity available.
   */
  public function getMin() {
    return $this->minimum;
  }

  /**
   * Gets the maximum quantity available.
   *
   * @return int
   *   The maximum quantity available.
   */
  public function getMax() {
    return $this->maximum;
  }

  /**
   * Gets the reason for the response.
   *
   * @return string
   *   The reason for the response.
   */
  public function getReason() {
    return $this->reason;
  }

  /**
   * Creates an AvailabilityResponseInterface object with isAvailable() === TRUE.
   *
   * @param int $min
   *   The minimum amount available.
   * @param int $max
   *   The maximum amount available.
   *
   * @return \Drupal\commerce\AvailabilityResponse\AvailabilityResponseAvailable
   *   isAvailable() will be TRUE.
   */
  public static function available($min, $max) {
    return new AvailabilityResponseAvailable($min, $max);
  }

  /**
   * Creates an AvailabilityResponseInterface object with isUnavailable() === TRUE.
   *
   * @param int $min
   *   The minimum amount available.
   * @param int $max
   *   The maximum amount available.
   * @param string|null $reason
   *   (optional) The reason why availability is unavailable.
   *   Intended for developers, hence not translatable.
   *
   * @return \Drupal\commerce\AvailabilityResponse\AvailabilityResponseUnavailable
   *   isUnavailable() will be TRUE.
   */
  public static function unavailable($min, $max, $reason = NULL) {
    assert('is_string($reason) || is_null($reason)');
    return new AvailabilityResponseUnavailable($min, $max, $reason);
  }

  /**
   * Creates an AvailabilityResponseInterface object with isNeutral() === TRUE.
   *
   * @return \Drupal\commerce\AvailabilityResponse\AvailabilityResponseNeutral
   *   isNeutral() will be TRUE.
   */
  public static function neutral() {
    return new AvailabilityResponseNeutral();
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\commerce\AvailabilityResponse\AvailabilityResponseAvailable
   */
  public function isAvailable() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\commerce\AvailabilityResponse\AvailabilityResponseUnavailable
   */
  public function isUnavailable() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Core\Access\AccessResultNeutral
   */
  public function isNeutral() {
    return FALSE;
  }

}
