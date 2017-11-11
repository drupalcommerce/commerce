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

  /**
   * Gets the order's checkout step ID.
   *
   * Ensures that the user is allowed to access the requested step ID,
   * when given. In case the requested step ID is empty, invalid, or
   * not allowed, a different step ID will be returned.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param string $requested_step_id
   *   (Optional) The requested step ID.
   *
   * @return string
   *   The checkout step ID.
   */
  public function getCheckoutStepId(OrderInterface $order, $requested_step_id = NULL);

}
