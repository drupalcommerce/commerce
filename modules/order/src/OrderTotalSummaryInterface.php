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
   *     - adjustments: An array of adjustment totals:
   *         - type: The adjustment type.
   *         - label: The adjustment label.
   *         - total: The adjustment total price.
   *         - weight: The adjustment weight, taken from the adjustment type.
   *     - total: The order total price.
   */
  public function buildTotals(OrderInterface $order);

}
