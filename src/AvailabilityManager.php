<?php

namespace Drupal\commerce;

use Drupal\commerce\AvailabilityResponse\AvailabilityResponse;

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
    $min = $max = 0;

    $has_applicable_checkers = FALSE;
    foreach ($this->checkers as $checker) {
      if ($checker->applies($entity)) {
        $has_applicable_checkers = TRUE;
        $response = $checker->check($entity, $quantity, $context);
        $min = min($min, $response->getMin());
        $max = max($max, $response->getMax());
      }
    }
    if (!$has_applicable_checkers) {
      return AvailabilityResponse::neutral();
    }

    if ($min <= $quantity && $quantity <= $max) {
      return AvailabilityResponse::available($min, $max);
    }
    elseif ($min > $quantity || $quantity > $max) {
      if ($min > $quantity) {
        return AvailabilityResponse::unavailable($min, $max, 'minimum not met');
      }
      elseif ($quantity > $max) {
        return AvailabilityResponse::unavailable($min, $max, 'maximum exceeded');
      }
    }

    return AvailabilityResponse::neutral();
  }

}
