<?php

namespace Drupal\commerce;

/**
 * An object representing a response to an availability request.
 */
class AvailabilityResponse implements AvailabilityResponseInterface {

  /**
   * The purchasable entity.
   *
   * @var \Drupal\commerce\PurchasableEntityInterface
   */
  protected $entity;

  /**
   * The context.
   *
   * @var \Drupal\commerce\Context
   */
  protected $context;

  /**
   * The minimum quantity available.
   *
   * @var int
   */
  protected $minimum;

  /**
   * The maximum quantity available.
   *
   * @var int
   */
  protected $maximum;

  /**
   * Constructs a new AvailabilityResponse object.
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
  public function __construct(PurchasableEntityInterface $entity, Context $context, $minimum, $maximum) {
    $this->entity = $entity;
    $this->context = $context;
    $this->minimum = $minimum;
    $this->maximum = $maximum;
  }

  public function getMin() {
    return $this->minimum;
  }

  public function getMax() {
    return $this->maximum;
  }

}