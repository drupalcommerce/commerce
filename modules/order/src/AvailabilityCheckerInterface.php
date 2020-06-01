<?php

namespace Drupal\commerce_order;

use Drupal\commerce\Context;
use Drupal\commerce_order\Entity\OrderItemInterface;

/**
 * Defines the interface for availability checkers.
 */
interface AvailabilityCheckerInterface {

  /**
   * Determines whether the checker applies to the given order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   *
   * @return bool
   *   TRUE if the checker applies to the given order item, FALSE otherwise.
   */
  public function applies(OrderItemInterface $order_item);

  /**
   * Checks the availability of the given order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   * @param \Drupal\commerce\Context $context
   *   The context.
   *
   * @return \Drupal\commerce_order\AvailabilityResult
   *   The availability result. AvailabilityResult::unavailable() should be
   *   used to indicate that the given order item is "unavailable" for purchase.
   *   Note that an optional "reason" can be specified.
   */
  public function check(OrderItemInterface $order_item, Context $context);

}
