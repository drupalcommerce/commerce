<?php

namespace Drupal\commerce;

use Drupal\commerce\AvailabilityResponse\NegativeResponse;
use Drupal\commerce\AvailabilityResponse\NeutralResponse;
use Drupal\commerce\AvailabilityResponse\PositiveResponse;


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
        $response = $checker->getAvailability($entity, $context);
        $min = min($min, $response->getMin());
        $max = max($max, $response->getMax());
      }
    }

    if (!$has_applicable_checkers) {
      return new NeutralResponse($entity, $context);
    }

    if ($min < $quantity) {
      return new NegativeResponse($entity, $context, $min, $max);
    }

    return new PositiveResponse($entity, $context, $min, $max);
  }

}
