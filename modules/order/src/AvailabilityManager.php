<?php

/**
 * @file
 * Contains \Drupal\commerce_order\AvailabilityManager.
 */

namespace Drupal\commerce_order;

use Drupal\commerce\LineItemSourceInterface;

class AvailabilityManager implements AvailabilityManagerInterface {

  /**
   * The availability checkers.
   *
   * @var array
   */
  protected $availabilityCheckers = [];

  /**
   * Adds availability checker to the registered availability checkers.
   *
   * @param \Drupal\commerce_order\AvailabilityCheckerInterface $availabilityChecker
   *   The availability checker.
   */
  public function addAvailabilityChecker(AvailabilityCheckerInterface $availabilityChecker) {
    $this->availabilityCheckers[] = $availabilityChecker;
  }

  /**
   * {@inheritdoc}
   */
  public function check(LineItemSourceInterface $source, $quantity) {
    foreach ($this->availabilityCheckers as $availabilityChecker) {
      if ($availabilityChecker->applies($source)) {
        $check = $availabilityChecker->check($source, $quantity);

        if ($check === FALSE) {
          return FALSE;
        }
      }
    }

    return TRUE;
  }

}
