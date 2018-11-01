<?php

namespace Drupal\commerce_order;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_price\Price;

/**
 * Splits price amounts across order items.
 *
 * Useful for dividing a single order-level promotion or fee into multiple
 * order-item-level ones, for easier VAT calculation or refunds.
 */
interface PriceSplitterInterface {

  /**
   * Splits the given amount across order items.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Drupal\commerce_price\Price $amount
   *   The amount.
   * @param string $percentage
   *   The percentage used to calculate the amount, as a decimal.
   *   For example, '0.2' for 20%. When missing, calculated by comparing
   *   the amount to the order subtotal.
   *
   * @return \Drupal\commerce_price\Price[]
   *   An array of amounts keyed by order item ID.
   */
  public function split(OrderInterface $order, Price $amount, $percentage = NULL);

}
