<?php

namespace Drupal\commerce_checkout;

use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Provides functionality for handling an order's checkout.
 */
interface CheckoutOrderManagerInterface {

  /**
   * Gets the order's checkout flow.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return \Drupal\commerce_checkout\Entity\CheckoutFlowInterface
   *   THe checkout flow.
   */
  public function getCheckoutFlow(OrderInterface $order);

}
