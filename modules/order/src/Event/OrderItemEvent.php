<?php

namespace Drupal\commerce_order\Event;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the order item event.
 *
 * @see \Drupal\commerce_order\Event\OrderEvents
 */
class OrderItemEvent extends Event {

  /**
   * The order item.
   *
   * @var \Drupal\commerce_order\Entity\OrderItemInterface
   */
  protected $orderItem;

  /**
   * Constructs a new OrderItemEvent.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   */
  public function __construct(OrderItemInterface $order_item) {
    $this->orderItem = $order_item;
  }

  /**
   * Gets the order item.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface
   *   The order item.
   */
  public function getOrderItem() {
    return $this->orderItem;
  }

}
