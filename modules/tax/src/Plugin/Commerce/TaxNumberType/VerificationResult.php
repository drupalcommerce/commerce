<?php

namespace Drupal\commerce_tax\Plugin\Commerce\TaxNumberType;

/**
 * Represents a tax number verification result.
 */
final class VerificationResult {

  /**
   * Available states.
   */
  const STATE_SUCCESS = 'success';
  const STATE_FAILURE = 'failure';
  const STATE_UNKNOWN = 'unknown';

  /**
   * The state.
   *
   * One of the STATE_ constants.
   *
   * @var string
   */
  protected $state;

  /**
   * The timestamp.
   *
   * @var int
   */
  protected $timestamp;

  /**
   * The data.
   *
   * @var string
   */
  protected $data;

  /**
   * Constructs a new VerificationResult object.
   *
   * @param string $state
   *   The state. One of the STATE_ constants.
   * @param int $timestamp
   *   The timestamp.
   * @param array $data
   *   The data. Optional.
   */
  public function __construct(string $state, int $timestamp, array $data = []) {
    $this->state = $state;
    $this->timestamp = $timestamp;
    $this->data = $data;
  }

  /**
   * Constructs a success result.
   *
   * @param int $timestamp
   *   The timestamp.
   * @param array $data
   *   The data. Optional.
   *
   * @return static
   */
  public static function success(int $timestamp, array $data = []) : VerificationResult {
    return new static(self::STATE_SUCCESS, $timestamp, $data);
  }

  /**
   * Constructs a failure result.
   *
   * @param int $timestamp
   *   The timestamp.
   * @param array $data
   *   The data. Optional.
   *
   * @return static
   */
  public static function failure(int $timestamp, array $data = []) : VerificationResult {
    return new static(self::STATE_FAILURE, $timestamp, $data);
  }

  /**
   * Constructs an unkown result.
   *
   * @param int $timestamp
   *   The timestamp.
   * @param array $data
   *   The data. Optional.
   *
   * @return static
   */
  public static function unknown(int $timestamp, array $data = []) : VerificationResult {
    return new static(self::STATE_UNKNOWN, $timestamp, $data);
  }

  /**
   * Gets whether the verification was successful.
   *
   * @return bool
   *   TRUE if the verification was successful, FALSE otherwise.
   */
  public function isSuccess() : bool {
    return $this->state == self::STATE_SUCCESS;
  }

  /**
   * Gets whether the verification failed.
   *
   * @return bool
   *   TRUE if the verification failed, FALSE otherwise.
   */
  public function isFailure() : bool {
    return $this->state == self::STATE_FAILURE;
  }

  /**
   * Gets whether the verification state is unknown.
   *
   * Used for cases where the remote service is temporarily unavailable.
   *
   * @return bool
   *   TRUE if the verification state is unknown, FALSE otherwise.
   */
  public function isUnknown() : bool {
    return $this->state == self::STATE_UNKNOWN;
  }

  /**
   * Gets the state.
   *
   * @return string
   *   The state. One of the STATE_ constants.
   */
  public function getState() : string {
    return $this->state;
  }

  /**
   * Gets the timestamp.
   *
   * @return int
   *   The timestamp.
   */
  public function getTimestamp() : int {
    return $this->timestamp;
  }

  /**
   * Gets the data.
   *
   * @return array
   *   The data.
   */
  public function getData() : array {
    return $this->data;
  }

}
