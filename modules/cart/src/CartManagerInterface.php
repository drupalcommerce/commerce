<?php

/**
 * @file
 * Contains \Drupal\commerce_cart\CartManagerInterface.
 */

namespace Drupal\commerce_cart;

use Drupal\commerce_order\Entity\LineItemInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce\PurchasableEntityInterface;

/**
 * Manages the cart order and its line items.
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
   *   Whether the line item should be combined with an existing matching one.
   * @param bool $save_cart
   *   Whether the cart should be saved after the operation.
   *
   * @return \Drupal\commerce_order\Entity\LineItemInterface
   *   The saved line item.
   */
  public function addEntity(OrderInterface $cart, PurchasableEntityInterface $entity, $quantity = 1, $combine = TRUE, $save_cart = TRUE);

  /**
   * Creates a line item for the given purchasable entity.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param int $quantity
   *   The quantity.
   *
   * @return \Drupal\commerce_order\Entity\LineItemInterface
   *   The created line item. Unsaved.
   */
  public function createLineItem(PurchasableEntityInterface $entity, $quantity = 1);

  /**
   * Adds the given line item to the given cart order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $cart
   *   The cart order.
   * @param \Drupal\commerce_order\Entity\LineItemInterface $line_item
   *   The line item.
   * @param bool $combine
   *   Whether the line item should be combined with an existing matching one.
   * @param bool $save_cart
   *   Whether the cart should be saved after the operation.
   *
   * @return \Drupal\commerce_order\Entity\LineItemInterface
   *   The saved line item.
   */
  public function addLineItem(OrderInterface $cart, LineItemInterface $line_item, $combine = TRUE, $save_cart = TRUE);

  /**
   * Updates the given line item.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $cart
   *   The cart order.
   * @param \Drupal\commerce_order\Entity\LineItemInterface $line_item
   *   The line item.
   */
  public function updateLineItem(OrderInterface $cart, LineItemInterface $line_item);

  /**
   * Removes the given line item from the cart order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $cart
   *   The cart order.
   * @param \Drupal\commerce_order\Entity\LineItemInterface $line_item
   *   The line item.
   * @param bool $save_cart
   *   Whether the cart should be saved after the operation.
   */
  public function removeLineItem(OrderInterface $cart, LineItemInterface $line_item, $save_cart = TRUE);

}
