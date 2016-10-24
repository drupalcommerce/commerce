<?php

namespace Drupal\commerce;

/**
 * Defines an interface for obtaining system time.
 *
 * A copy of the TimeInterface that exists in Drupal 8.3.x.
 *
 * @todo Replace with the core interface once we start depending on 8.3.x.
 */
interface TimeInterface {

  /**
   * Returns the timestamp for the current request.
   *
   * This method should be used to obtain the current system time at the start
   * of the request. It will be the same value for the life of the request
   * (even for long execution times).
   *
   * Using the time service, rather than other methods, is especially important
   * when creating tests, which require predictable timestamps.
   *
   * @return int
   *   A Unix timestamp.
   *
   * @see \Drupal\Component\Datetime\TimeInterface::getRequestMicroTime()
   * @see \Drupal\Component\Datetime\TimeInterface::getCurrentTime()
   * @see \Drupal\Component\Datetime\TimeInterface::getCurrentMicroTime()
   */
  public function getRequestTime();

  /**
   * Returns the timestamp for the current request with microsecond precision.
   *
   * This method should be used to obtain the current system time, with
   * microsecond precision, at the start of the request. It will be the same
   * value for the life of the request (even for long execution times).
   *
   * Using the time service, rather than other methods, is especially important
   * when creating tests, which require predictable timestamps.
   *
   * @return float
   *   A Unix timestamp with a fractional portion.
   *
   * @see \Drupal\Component\Datetime\TimeInterface::getRequestTime()
   * @see \Drupal\Component\Datetime\TimeInterface::getCurrentTime()
   * @see \Drupal\Component\Datetime\TimeInterface::getCurrentMicroTime()
   */
  public function getRequestMicroTime();

  /**
   * Returns the current system time as an integer.
   *
   * This method should be used to obtain the current system time, at the time
   * the method was called.
   *
   * This method should only be used when the current system time is actually
   * needed, such as with timers or time interval calculations. If only the
   * time at the start of the request is needed,
   * use TimeInterface::getRequestTime().
   *
   * Using the time service, rather than other methods, is especially important
   * when creating tests, which require predictable timestamps.
   *
   * @return int
   *   A Unix timestamp.
   *
   * @see \Drupal\Component\Datetime\TimeInterface::getRequestTime()
   * @see \Drupal\Component\Datetime\TimeInterface::getRequestMicroTime()
   * @see \Drupal\Component\Datetime\TimeInterface::getCurrentMicroTime()
   */
  public function getCurrentTime();

  /**
   * Returns the current system time with microsecond precision.
   *
   * This method should be used to obtain the current system time, with
   * microsecond precision, at the time the method was called.
   *
   * This method should only be used when the current system time is actually
   * needed, such as with timers or time interval calculations. If only the
   * time at the start of the request and microsecond precision is needed,
   * use TimeInterface::getRequestMicroTime().
   *
   * Using the time service, rather than other methods, is especially important
   * when creating tests, which require predictable timestamps.
   *
   * @return float
   *   A Unix timestamp with a fractional portion.
   *
   * @see \Drupal\Component\Datetime\TimeInterface::getRequestTime()
   * @see \Drupal\Component\Datetime\TimeInterface::getRequestMicroTime()
   * @see \Drupal\Component\Datetime\TimeInterface::getCurrentTime()
   */
  public function getCurrentMicroTime();

}
