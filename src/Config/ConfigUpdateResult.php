<?php

namespace Drupal\commerce\Config;

/**
 * Result object for displaying configuration update results.
 */
class ConfigUpdateResult {

  /**
   * The configuration names that succeeded to update.
   *
   * @var \Drupal\Component\Render\MarkupInterface[]
   */
  protected $succeeded = [];

  /**
   * The configuration names that failed to update.
   *
   * @var \Drupal\Component\Render\MarkupInterface[]
   */
  protected $failed = [];

  /**
   * Constructs a new ConfigUpdateResult object.
   *
   * @param array $succeeded
   *   An array of configuration names that succeeded to update.
   * @param array $failed
   *   An array of configuration names that failed to update.
   */
  public function __construct(array $succeeded, array $failed) {
    $this->succeeded = $succeeded;
    $this->failed = $failed;
  }

  /**
   * Gets the configuration object names that succeeded to update.
   *
   * @return \Drupal\Component\Render\MarkupInterface[]
   *   An array of messages, keyed by configuration name.
   */
  public function getSucceeded() {
    return $this->succeeded;
  }

  /**
   * Gets the configuration object names that failed to update.
   *
   * @return \Drupal\Component\Render\MarkupInterface[]
   *   An array of messages, keyed by configuration name.
   */
  public function getFailed() {
    return $this->failed;
  }

}
