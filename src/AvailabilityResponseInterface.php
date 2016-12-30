<?php

namespace Drupal\commerce;

/**
 * Defines the interface for responses to availability checks.
 */
interface AvailabilityResponseInterface {

  /**
   * AvailabilityResponseInterface constructor.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param \Drupal\commerce\Context $context
   *   The context.
   * @param int $minimum
   *   The minimum quantity available.
   * @param int $maximum
   *   The maximum quantity available.
   */
  public function __construct(PurchasableEntityInterface $entity, Context $context, $minimum, $maximum);

  /**
   * Gets the minimum quantity available for the given entity and context.
   *
   * @return int
   */
  public function getMin();

  /**
   * Gets the maximum quantity available for the given entity and context.
   *
   * @return int
   */
  public function getMax();

}
