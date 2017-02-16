<?php

namespace Drupal\commerce_cart\Event;

use Drupal\commerce_order\Entity\OrderInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the cart empty event.
 *
 * @see \Drupal\commerce_cart\Event\CartEvents
 */
class CartEmptyEvent extends Event {

  /**
   * The cart order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $cart;

  /**
   * The removed order items.
   *
   * @var \Drupal\commerce_order\Entity\OrderItemInterface[]
   */
  protected $orderItems;

  /**
   * Constructs a new CartEmptyEvent.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $cart
   *   The cart order.
   * @param \Drupal\commerce_order\Entity\OrderItemInterface[] $order_items
   *   The removed order items.
   */
  public function __construct(OrderInterface $cart, array $order_items) {
    $this->cart = $cart;
    $this->orderItems = $order_items;
  }

  /**
   * Gets the cart order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The cart order.
   */
  public function getCart() {
    return $this->cart;
  }

  /**
   * Gets the removed order items.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface[]
   *   The removed order items.
   */
  public function getOrderItems() {
    return $this->orderItems;
  }

}
