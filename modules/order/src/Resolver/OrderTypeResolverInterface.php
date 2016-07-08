<?php

namespace Drupal\commerce_order\Resolver;

use Drupal\commerce_order\Entity\LineItemInterface;

/**
 * Defines the interface for order type resolvers.
 */
interface OrderTypeResolverInterface {

  /**
   * Resolves the order type.
   *
   * @param \Drupal\commerce_order\Entity\LineItemInterface $line_item
   *   The line item being added to an order.
   *
   * @return \Drupal\commerce_order\Entity\OrderTypeInterface|null
   *   The order type, if resolved. Otherwise NULL, indicating that the next
   *   resolver in the chain should be called.
   */
  public function resolve(LineItemInterface $line_item);

}
