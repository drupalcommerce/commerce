<?php

namespace Drupal\commerce;

/**
 * Default implementation of the availability manager.
 *
 * @deprecated in commerce:8.x-2.18 and is removed from commerce:3.x.
 *   Use \Drupal\commerce_order\AvailabilityManager instead.
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
    @trigger_error('The ' . get_class($this) . ' is deprecated in commerce:8.x-2.18 and is removed from commerce:3.x. Use \Drupal\commerce_order\AvailabilityManager instead.', E_USER_DEPRECATED);
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
