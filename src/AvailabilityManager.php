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
  public function getAvailability(PurchasableEntityInterface $entity, $quantity, Context $context) {
    $min = 0;
    $max = 0;
    $has_applicable_checkers = FALSE;
    foreach ($this->checkers as $checker) {
      if ($checker->applies($entity)) {
        $has_applicable_checkers = TRUE;
        $available = $checker->getAvailability($entity, $context);
        $min = min($min, $available->getMin());
        $max = max($max, $available->getMax());
      }
    }

    // @todo Find a cleaner way to deal with 'no opinion' / 'not stocked', i.e. NULL.
    if (!$has_applicable_checkers) {
      return new AvailabilityResponse($entity, $context, 0, 9999999999999999);
    }

    return new AvailabilityResponse($entity, $context, $min, $max);
  }

}
