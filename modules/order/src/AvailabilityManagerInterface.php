<?php

/**
 * @file
 * Contains \Drupal\commerce_order\AvailabilityManagerInterface.
 */

namespace Drupal\commerce_order;

use Drupal\commerce\LineItemSourceInterface;

/**
 * Runs the added checkers to determine the availability of a source entity.
 *
 * If any checker returns FALSE, the entity is considered to be unavailable.
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
   * Gets all added checkers.
   *
   * @return \Drupal\commerce_order\AvailabilityCheckerInterface[]
   *   The checkers.
   */
  public function getCheckers();

  /**
   * Checks the availability of the given source entity.
   *
   * @param \Drupal\commerce\LineItemSourceInterface $source
   *   The source entity.
   * @param int $quantity
   *   The quantity.
   *
   * @return bool
   *   TRUE if the source entity is available, FALSE otherwise.
   */
  public function check(LineItemSourceInterface $source, $quantity);

}
