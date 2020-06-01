<?php

namespace Drupal\commerce_order;

use Drupal\commerce\AvailabilityCheckerInterface as LegacyCheckerInterface;
use Drupal\commerce\Context;
use Drupal\commerce_order\Entity\OrderItemInterface;

/**
 * Runs the added checkers to determine the availability of an order item.
 *
 * If any checker returns an "unavailable" availability result,
 * the order item is considered to be unavailable.
 *
 * Example checks:
 * - Whether the entity is in stock.
 * - Whether the entity's "available on" date is before the current date.
 *
 * @see \Drupal\commerce_order\AvailabilityCheckerInterface
 */
interface AvailabilityManagerInterface {

  /**
   * Adds a checker.
   *
   * @param \Drupal\commerce_order\AvailabilityCheckerInterface $checker
   *   The checker.
   */
  public function addChecker(AvailabilityCheckerInterface $checker);

  /**
   * Adds a "legacy" (i.e "deprecated") checker.
   *
   * @param \Drupal\commerce\AvailabilityCheckerInterface $checker
   *   The "legacy" (i.e "deprecated") checker.
   */
  public function addLegacyChecker(LegacyCheckerInterface $checker);

  /**
   * Checks the availability of the given order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The the order item.
   * @param \Drupal\commerce\Context $context
   *   The context.
   *
   * @return \Drupal\commerce_order\AvailabilityResult
   *   An AvailabilityResult value object determining whether an order item
   *   is available for purchase.
   */
  public function check(OrderItemInterface $order_item, Context $context) : AvailabilityResult;

}
