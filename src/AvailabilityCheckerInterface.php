<?php

namespace Drupal\commerce;

/**
 * Defines the interface for availability checkers.
 */
interface AvailabilityCheckerInterface {

  /**
   * Determines whether the checker applies to the given purchasable entity.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   *
   * @return bool
   *   TRUE if the checker applies to the given purchasable entity, FALSE
   *   otherwise.
   */
  public function applies(PurchasableEntityInterface $entity);

  /**
   * Checks the availability of a given purchasable entity with given context.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param int $quantity
   *   The quantity.
   * @param \Drupal\commerce\Context $context
   *   The context.
   *
   * @return \Drupal\commerce\AvailabilityResponseInterface
   *   An AvailabilityResponse object.
   */
  public function check(PurchasableEntityInterface $entity, $quantity, Context $context);

}
