<?php

namespace Drupal\commerce;

/**
 * Defines the interface for availability checkers.
 *
 * @deprecated in commerce:8.x-2.18 and is removed from commerce:3.x.
 *   Use \Drupal\commerce_order\AvailabilityCheckerInterface instead.
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
   * Checks the availability of the given purchasable entity.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param string $quantity
   *   The quantity.
   * @param \Drupal\commerce\Context $context
   *   The context.
   *
   * @return bool|null
   *   TRUE if the entity is available, FALSE if it's unavailable,
   *   or NULL if it has no opinion.
   */
  public function check(PurchasableEntityInterface $entity, $quantity, Context $context);

}
