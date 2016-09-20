<?php

namespace Drupal\commerce_order;

use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Defines an interface for order processors.
 *
 * Order processors modify/handle/rebuild orders during the refresh process.
 */
interface OrderProcessorInterface {

  /**
   * Processes an order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  public function process(OrderInterface $order);

}
