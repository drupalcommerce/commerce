<?php

namespace Drupal\commerce_payment;

use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Updates orders based on payment information.
 *
 * When a payment is completed or refunded, the parent order's total_paid
 * field must be recalculated.
 *
 * If the payment tries to update the parent order right away, it might
 * generate a conflict, due to the order being edited elsewhere
 * (e.g., a payment gateway's onReturn() method creating a payment, and
 * then saving its own copy of the order). To avoid this problem, the updater
 * allows requesting an update, which is then applied on the next order save.
 * Any orders not saved by the end of the request will be saved when the
 * KernelSubscriber calls the updater for final updates.
 *
 * @see \Drupal\commerce_payment\PaymentOrderProcessor
 * @see \Drupal\commerce_payment\EventSubscriber\KernelSubscriber
 */
interface PaymentOrderUpdaterInterface {

  /**
   * Requests an update of the given order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  public function requestUpdate(OrderInterface $order);

  /**
   * Checks whether the given order needs to be updated.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return bool
   *   TRUE if an update was requested, FALSE otherwise.
   */
  public function needsUpdate(OrderInterface $order);

  /**
   * Updates and saves all relevant orders.
   */
  public function updateOrders();

  /**
   * Updates the given order.
   *
   * The order's total_paid field will be recalculated to reflect the
   * current payment total.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param bool $save_order
   *   Whether the order should be saved after the update. Always skipped
   *   if the total_paid field hasn't changed.
   */
  public function updateOrder(OrderInterface $order, $save_order = FALSE);

}
