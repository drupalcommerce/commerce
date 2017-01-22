<?php

namespace Drupal\commerce;

/**
 * Runs the added checkers to determine the availability of a purchasable entity.
 *
 * If any checker returns FALSE, the entity is considered to be unavailable.
 * Example checks:
 * - Whether the entity is in stock.
 * - Whether the entity's "available on" date is before the current date.
 *
 * @see \Drupal\commerce\AvailabilityCheckerInterface
 */
interface AvailabilityManagerInterface {

  /**
   * Adds a checker.
   *
   * @param \Drupal\commerce\AvailabilityCheckerInterface $checker
   *   The checker.
   */
  public function addChecker(AvailabilityCheckerInterface $checker);

  /**
   * Gets all added checkers.
   *
   * @return \Drupal\commerce\AvailabilityCheckerInterface[]
   *   The checkers.
   */
  public function getCheckers();

  /**
   * Gets an availability response from the aggregate of all checkers.
   *
   * Possible \Drupal\commerce\AvailabilityResponseInterface classes:
   * - Neutral: when no checker has an opinion about availability.
   * - Available: when at lease one checker responds with availability.
   * - Unavailable: when one of the checkers responds with unavailability.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param int $quantity
   *   The quantity.
   * @param \Drupal\commerce\Context $context
   *   The context.
   *
   * @return \Drupal\commerce\AvailabilityResponseInterface
   *   The availability response.
   */
  public function check(PurchasableEntityInterface $entity, $quantity, Context $context);

}
