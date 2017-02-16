<?php

namespace Drupal\commerce_cart;

use Drupal\commerce_order\Entity\OrderItemInterface;

/**
 * Finds matching order items.
 *
 * Used for combining order items in the add to cart process.
 */
interface OrderItemMatcherInterface {

  /**
   * Finds the best matching order item for the given order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   * @param \Drupal\commerce_order\Entity\OrderItemInterface[] $order_items
   *   The order items to match against.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface|null
   *   A matching order item, or NULL if none was found.
   */
  public function match(OrderItemInterface $order_item, array $order_items);

  /**
   * Finds all matching order items for the given order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   * @param \Drupal\commerce_order\Entity\OrderItemInterface[] $order_items
   *   The order items to match against.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface[]
   *   The matching order items.
   */
  public function matchAll(OrderItemInterface $order_item, array $order_items);

}
