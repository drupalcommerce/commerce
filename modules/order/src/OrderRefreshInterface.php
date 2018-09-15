<?php

namespace Drupal\commerce_order;

use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Runs order refresh on draft orders.
 */
interface OrderRefreshInterface {

  /**
   * Adds an order processor.
   *
   * @param \Drupal\commerce_order\OrderProcessorInterface $processor
   *   The order processor.
   */
  public function addProcessor(OrderProcessorInterface $processor);

  /**
   * Checks whether the order should be refreshed.
   *
   * Wraps the needsRefresh() check with an additional refresh mode check,
   * skipping the refresh for non-customers when specified.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return bool
   *   TRUE if the order should be refreshed, FALSE otherwise.
   */
  public function shouldRefresh(OrderInterface $order);

  /**
   * Checks whether the given order needs to be refreshed.
   *
   * An order needs to be refreshed:
   * - If a refresh was explicitly requested via $order->setNeedsRefresh() due
   *   to the order being modified.
   * - If it was not refreshed today (date changes can affect tax rate amounts,
   *   promotion availability)
   * - If it was not refreshed for longer than the refresh frequency.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return bool
   *   TRUE if the order needs to be refreshed, FALSE otherwise.
   */
  public function needsRefresh(OrderInterface $order);

  /**
   * Refreshes the given order.
   *
   * Any modified order items will be automatically saved.
   * The order itself will not be saved.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  public function refresh(OrderInterface $order);

}
