<?php

namespace Drupal\commerce_discount\Event;

use Drupal\commerce_discount\Entity\DiscountInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the discount event.
 *
 * @see \Drupal\commerce_discount\Event\DiscountEvents
 */
class DiscountEvent extends Event {

  /**
   * The discount.
   *
   * @var \Drupal\commerce_discount\Entity\DiscountInterface
   */
  protected $discount;

  /**
   * Constructs a new DiscountEvent.
   *
   * @param \Drupal\commerce_discount\Entity\DiscountInterface $discount
   *   The discount.
   */
  public function __construct(DiscountInterface $discount) {
    $this->discount = $discount;
  }

  /**
   * Gets the discount.
   *
   * @return \Drupal\commerce_discount\Entity\DiscountInterface
   *   The discount.
   */
  public function getDiscount() {
    return $this->discount;
  }

}
