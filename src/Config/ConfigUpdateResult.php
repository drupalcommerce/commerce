<?php

namespace Drupal\commerce\Config;

/**
 * Result object for configuration updates.
 */
class ConfigUpdateResult {

  /**
   * The success messages keyed by config name.
   *
   * @var string[]
   */
  protected $succeeded = [];

  /**
   * The failure messages keyed by config name.
   *
   * @var string[]
   */
  protected $failed = [];

  /**
   * Constructs a new ConfigUpdateResult object.
   *
   * @param string[] $succeeded
   *   The success messages keyed by config name.
   * @param string[] $failed
   *   The failure messages keyed by config name.
   */
  public function __construct(array $succeeded, array $failed) {
    $this->succeeded = $succeeded;
    $this->failed = $failed;
  }

  /**
   * Gets the success messages.
   *
   * @return string[]
   *   The success messages keyed by config name.
   */
  public function getSucceeded() {
    return $this->succeeded;
  }

  /**
   * Gets the failure messages.
   *
   * @return string[]
   *   The failure messages keyed by config name.
   */
  public function getFailed() {
    return $this->failed;
  }

}
