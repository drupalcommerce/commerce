<?php

namespace Drupal\commerce_cart\Event;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the cart entity add event.
 *
 * @see \Drupal\commerce_cart\Event\CartEvents
 */
class CartEntityAddEvent extends Event {

  /**
   * The cart order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $cart;

  /**
   * The added entity.
   *
   * @var \Drupal\commerce\PurchasableEntityInterface
   */
  protected $entity;

  /**
   * The quantity.
   *
   * @var float
   */
  protected $quantity;

  /**
   * The destination order item.
   *
   * @var \Drupal\commerce_order\Entity\OrderItemInterface
   */
  protected $orderItem;

  /**
   * Constructs a new CartOrderItemEvent.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $cart
   *   The cart order.
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The added entity.
   * @param float $quantity
   *   The quantity.
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The destination order item.
   */
  public function __construct(OrderInterface $cart, PurchasableEntityInterface $entity, $quantity, OrderItemInterface $order_item) {
    $this->cart = $cart;
    $this->entity = $entity;
    $this->quantity = $quantity;
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
   * Gets the added entity.
   *
   * @return \Drupal\commerce\PurchasableEntityInterface
   *   The added entity.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Gets the quantity.
   *
   * @return float
   *   The quantity.
   */
  public function getQuantity() {
    return $this->quantity;
  }

  /**
   * Gets the destination order item.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface
   *   The destination order item.
   */
  public function getOrderItem() {
    return $this->orderItem;
  }

}
