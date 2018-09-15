<?php

namespace Drupal\commerce_order\Resolver;

use Drupal\commerce_order\Entity\OrderItemInterface;

/**
 * Defines the interface for order type resolvers.
 */
interface OrderTypeResolverInterface {

  /**
   * Resolves the order type.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item being added to an order.
   *
   * @return string|null
   *   The order type ID, if resolved. Otherwise NULL, indicating that the
   *   next resolver in the chain should be called.
   */
  public function resolve(OrderItemInterface $order_item);

}
