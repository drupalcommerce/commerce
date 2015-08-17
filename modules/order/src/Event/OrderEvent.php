<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Event\OrderEvent.
 */

namespace Drupal\commerce_order\Event;

use Drupal\commerce_order\OrderInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the order event.
 *
 * @see \Drupal\commerce_order\Event\OrderEvents
 */
class OrderEvent extends Event {

  /**
   * The order.
   *
   * @var \Drupal\commerce_order\OrderInterface
   */
  protected $order;

  /**
   * Constructs a new OrderEvent.
   *
   * @param \Drupal\commerce_order\OrderInterface $order
   *   The order.
   */
  public function __construct(OrderInterface $order) {
    $this->order = $order;
  }

  /**
   * The order the event refers to.
   *
   * @return \Drupal\commerce_order\OrderInterface
   */
  public function getOrder() {
    return $this->order;
  }

}
