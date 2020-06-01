<?php

namespace Drupal\commerce_order;

use Drupal\commerce\AvailabilityCheckerInterface as LegacyAvailabilityCheckerInterface;
use Drupal\commerce\Context;
use Drupal\commerce_order\Entity\OrderItemInterface;

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
   * The "legacy" checkers.
   *
   * @var \Drupal\commerce\AvailabilityCheckerInterface[]
   */
  protected $legacyCheckers = [];

  /**
   * {@inheritdoc}
   */
  public function addChecker(AvailabilityCheckerInterface $checker) {
    $this->checkers[] = $checker;
  }

  /**
   * {@inheritdoc}
   */
  public function addLegacyChecker(LegacyAvailabilityCheckerInterface $checker) {
    $this->legacyCheckers[] = $checker;
  }

  /**
   * {@inheritdoc}
   */
  public function check(OrderItemInterface $order_item, Context $context) : AvailabilityResult {
    foreach ($this->checkers as $checker) {
      if (!$checker->applies($order_item)) {
        continue;
      }
      $result = $checker->check($order_item, $context);
      if ($result instanceof AvailabilityResult && $result->isUnavailable()) {
        return $result;
      }
    }

    // Invoke the legacy checkers next, and wrap the return value into our
    // new AvailabilityResult value object.
    $purchased_entity = $order_item->getPurchasedEntity();
    $quantity = $order_item->getQuantity();
    foreach ($this->legacyCheckers as $checker) {
      @trigger_error(get_class($checker) . ' implements \Drupal\commerce\AvailabilityCheckerInterface which is deprecated in commerce:8.x-2.18 and is removed from commerce:3.x. use \Drupal\commerce_order\AvailabilityCheckerInterface instead.', E_USER_DEPRECATED);
      if (!$checker->applies($purchased_entity)) {
        continue;
      }
      $result = $checker->check($purchased_entity, $quantity, $context);
      if ($result === FALSE) {
        return AvailabilityResult::unavailable();
      }
    }

    return AvailabilityResult::neutral();
  }

}
