<?php

/**
 * @file
 * Contains \Drupal\commerce_order\AvailabilityManager.
 */

namespace Drupal\commerce_order;

use Drupal\commerce\PurchasableEntityInterface;

/**
 * Default implementation of the availability manager.
 */
class AvailabilityManager implements AvailabilityManagerInterface {

  /**
   * The checkers.
   *
   * @var \Drupal\commerce_order\AvailabilityCheckerInterface[]
   */
  protected $checkers = [];

  /**
   * {@inheritdoc}
   */
  public function addChecker(AvailabilityCheckerInterface $checker) {
    $this->checkers[] = $checker;
  }

  /**
   * {@inheritdoc}
   */
  public function getCheckers() {
    return $this->checkers;
  }

  /**
   * {@inheritdoc}
   */
  public function check(PurchasableEntityInterface $entity, $quantity = 1) {
    foreach ($this->checkers as $checker) {
      if ($checker->applies($entity)) {
        $result = $checker->check($entity, $quantity);
        if ($result === FALSE) {
          return FALSE;
        }
      }
    }

    return TRUE;
  }

}
