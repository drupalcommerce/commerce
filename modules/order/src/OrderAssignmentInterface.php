<?php

namespace Drupal\commerce_order;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\user\UserInterface;

/**
 * Handles assigning orders to customers.
 */
interface OrderAssignmentInterface {

  /**
   * Assigns the order to the given customer.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Drupal\user\UserInterface $customer
   *   The customer.
   */
  public function assign(OrderInterface $order, UserInterface $customer);

  /**
   * Assigns multiple orders to the given customer.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface[] $orders
   *   The orders.
   * @param \Drupal\user\UserInterface $customer
   *   The customer.
   */
  public function assignMultiple(array $orders, UserInterface $customer);

}
