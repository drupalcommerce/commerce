<?php

namespace Drupal\commerce_order;

use Drupal\commerce_order\Entity\OrderInterface;

interface OrderTotalSummaryInterface {

  /**
   * Builds the totals for the given order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return array
   *   An array of totals with the following elements:
   *     - subtotal: The order subtotal price.
   *     - adjustments: The adjustments:
   *         - type: The adjustment type.
   *         - label: The adjustment label.
   *         - amount: The adjustment amount.
   *         - percentage: The decimal adjustment percentage, when available.
   *     - total: The order total price.
   */
  public function buildTotals(OrderInterface $order);

}
