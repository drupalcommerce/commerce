<?php

namespace Drupal\commerce_cart\Event;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the cart order item update event.
 *
 * @see \Drupal\commerce_cart\Event\CartEvents
 */
class CartOrderItemUpdateEvent extends Event {

  /**
   * The cart order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $cart;

  /**
   * The updated order item.
   *
   * @var \Drupal\commerce_order\Entity\OrderItemInterface
   */
  protected $orderItem;

  /**
   * The original order item.
   *
   * @var \Drupal\commerce_order\Entity\OrderItemInterface
   */
  protected $originalOrderItem;

  /**
   * Constructs a new CartOrderItemUpdateEvent.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $cart
   *   The cart order.
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The updated order item.
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $original_order_item
   *   The original order item.
   */
  public function __construct(OrderInterface $cart, OrderItemInterface $order_item, OrderItemInterface $original_order_item) {
    $this->cart = $cart;
    $this->orderItem = $order_item;
    $this->originalOrderItem = $original_order_item;
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
   * Gets the updated order item.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface
   *   The updated order item.
   */
  public function getOrderItem() {
    return $this->orderItem;
  }

  /**
   * Gets the original order item.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface
   *   The original order item.
   */
  public function getOriginalOrderItem() {
    return $this->originalOrderItem;
  }

}
