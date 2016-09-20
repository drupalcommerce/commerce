<?php

namespace Drupal\commerce_order;

use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Runs order refresh on draft orders.
 */
interface OrderRefreshInterface {

  /**
   * Adds an order refresh processor.
   *
   * @param \Drupal\commerce_order\OrderProcessorInterface $processor
   *   The order refresh processor.
   * @param int $priority
   *   The processor's priority.
   */
  public function addProcessor(OrderProcessorInterface $processor, $priority);

  /**
   * Checks if an order needs a refresh.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return bool
   *   TRUE if the order needs to be refreshed.
   */
  public function needsRefresh(OrderInterface $order);

  /**
   * Refreshes an order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The order, refreshed.
   */
  public function refresh(OrderInterface $order);

}
