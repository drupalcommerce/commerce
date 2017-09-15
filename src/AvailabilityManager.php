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
    $min = defined('PHP_INT_MIN') ? PHP_INT_MIN : -2147483648;
    $max = PHP_INT_MAX;

    $has_opinion = FALSE;
    foreach ($this->checkers as $checker) {
      if ($checker->applies($entity)) {
        $response = $checker->check($entity, $quantity, $context);
        if ($response instanceof AvailabilityResponseUnavailable) {
          return $response;
        }
        if ($response instanceof AvailabilityResponseNeutral) {
          continue;
        }
        $has_opinion = TRUE;
        $min = max($min, $response->getMin());
        $max = min($max, $response->getMax());
      }
    }
    if (!$has_opinion) {
      return AvailabilityResponse::neutral();
    }
    elseif ($min <= $quantity && $quantity <= $max) {
      return AvailabilityResponse::available($min, $max);
    }
    elseif ($min > $quantity || $quantity > $max) {
      $reason = ($min > $quantity) ? 'minimum not met' : 'maximum exceeded';
      return AvailabilityResponse::unavailable($min, $max, $reason);
    }
  }

}
