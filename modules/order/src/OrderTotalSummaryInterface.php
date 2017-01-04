<?php

namespace Drupal\commerce_order;

use Drupal\commerce_order\Entity\OrderInterface;

interface OrderTotalSummaryInterface {

  /**
   * Returns the line item, adjustment, and grand total for an order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return array
   *   An array of totals with the following elements:
   *     - total: \Drupal\commerce_price\Price|null The order total.
   *     - subtotal: \Drupal\commerce_price\Price|null The order subtotal.
   *     - adjustments: array An array of all grouped adjustments:
   *       - <type>: An array keyed by adjustment type id.
   *         - label: string The adjustment type label.
   *         - weight: int The weight of the adjustment type.
   *         - items: An associative array of items, grouped by type and source id
   *           if a source id exists, otherwise a numerically indexed item, each
   *           with the following keys:
   *           - amount: \Drupal\commerce_price\Price The total.
   *           - label: string The label of the adjustment or adjustment type.
   */
  public function buildTotals(OrderInterface $order);

}
