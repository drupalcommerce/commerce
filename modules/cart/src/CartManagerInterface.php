<?php

namespace Drupal\commerce_cart;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce\PurchasableEntityInterface;

/**
 * Manages the cart order and its order items.
 */
interface CartManagerInterface {

  /**
   * Empties the given cart order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $cart
   *   The cart order.
   * @param bool $save_cart
   *   Whether the cart should be saved after the operation.
   */
  public function emptyCart(OrderInterface $cart, $save_cart = TRUE);

  /**
   * Adds the given purchasable entity to the given cart order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $cart
   *   The cart order.
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param int $quantity
   *   The quantity.
   * @param bool $combine
   *   Whether the order item should be combined with an existing matching one.
   * @param bool $save_cart
   *   Whether the cart should be saved after the operation.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface
   *   The saved order item.
   */
  public function addEntity(OrderInterface $cart, PurchasableEntityInterface $entity, $quantity = 1, $combine = TRUE, $save_cart = TRUE);

  /**
   * Creates an order item for the given purchasable entity.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param int $quantity
   *   The quantity.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface
   *   The created order item. Unsaved.
   */
  public function createOrderItem(PurchasableEntityInterface $entity, $quantity = 1);

  /**
   * Adds the given order item to the given cart order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $cart
   *   The cart order.
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   * @param bool $combine
   *   Whether the order item should be combined with an existing matching one.
   * @param bool $save_cart
   *   Whether the cart should be saved after the operation.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface
   *   The saved order item.
   */
  public function addOrderItem(OrderInterface $cart, OrderItemInterface $order_item, $combine = TRUE, $save_cart = TRUE);

  /**
   * Updates the given order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $cart
   *   The cart order.
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   * @param bool $save_cart
   *   Whether the cart should be saved after the operation.
   */
  public function updateOrderItem(OrderInterface $cart, OrderItemInterface $order_item, $save_cart = TRUE);

  /**
   * Removes the given order item from the cart order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $cart
   *   The cart order.
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   * @param bool $save_cart
   *   Whether the cart should be saved after the operation.
   */
  public function removeOrderItem(OrderInterface $cart, OrderItemInterface $order_item, $save_cart = TRUE);

}
