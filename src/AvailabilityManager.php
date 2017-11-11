<?php

namespace Drupal\commerce;

/**
 * Default implementation of the availability manager.
 */
class AvailabilityManager implements AvailabilityManagerInterface {

  /**
   * The checkers.
   *
   * @var \Drupal\commerce\AvailabilityCheckerInterface[]
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
  public function check(PurchasableEntityInterface $entity, $quantity, Context $context) {
    foreach ($this->checkers as $checker) {
      if ($checker->applies($entity)) {
        $result = $checker->check($entity, $quantity, $context);
        if ($result === FALSE) {
          return FALSE;
        }
      }
    }

    return TRUE;
  }

}
