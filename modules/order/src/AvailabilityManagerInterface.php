<?php

/**
 * @file
 * Contains \Drupal\commerce_order\AvailabilityManagerInterface.
 */

namespace Drupal\commerce_order;

use Drupal\commerce\PurchasableEntityInterface;

/**
 * Runs the added checkers to determine the availability of a purchasable entity.
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
   * Checks the availability of the given purchasable entity.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param int $quantity
   *   The quantity.
   *
   * @return bool
   *   TRUE if the purchasable entity is available, FALSE otherwise.
   */
  public function check(PurchasableEntityInterface $entity, $quantity = 1);

}
