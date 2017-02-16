<?php

namespace Drupal\commerce_cart\Event;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the cart order item remove event.
 *
 * @see \Drupal\commerce_cart\Event\CartEvents
 */
class CartOrderItemRemoveEvent extends Event {

  /**
   * The cart order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $cart;

  /**
   * The removed order item.
   *
   * @var \Drupal\commerce_order\Entity\OrderItemInterface
   */
  protected $orderItem;

  /**
   * Constructs a new CartOrderItemRemoveEvent.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $cart
   *   The cart order.
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The removed order item.
   */
  public function __construct(OrderInterface $cart, OrderItemInterface $order_item) {
    $this->cart = $cart;
    $this->orderItem = $order_item;
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
   * Gets the removed order item.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface
   *   The order item entity.
   */
  public function getOrderItem() {
    return $this->orderItem;
  }

}
