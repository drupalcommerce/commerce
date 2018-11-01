<?php

namespace Drupal\commerce_payment;

use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Updates orders based on payment information.
 */
interface PaymentOrderManagerInterface {

  /**
   * Recalculates the total paid price for the given order.
   *
   * The order will be saved if the total paid price has changed.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  public function updateTotalPaid(OrderInterface $order);

}
