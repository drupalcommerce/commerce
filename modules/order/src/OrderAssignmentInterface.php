<?php

namespace Drupal\commerce_order;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\user\UserInterface;

/**
 * Handles assigning anonymous orders to user accounts.
 */
interface OrderAssignmentInterface {

  /**
   * Assigns the anonymous order to the given user account.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Drupal\user\UserInterface $account
   *   The user account.
   * @param bool $force
   *   If we should reassign even when it already is.
   */
  public function assign(OrderInterface $order, UserInterface $account, $force = FALSE);

  /**
   * Assigns multiple anonymous orders to the given user account.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface[] $orders
   *   The orders.
   * @param \Drupal\user\UserInterface $account
   *   The user account.
   * @param bool $force
   *   If we should reassign even when it already is.
   */
  public function assignMultiple(array $orders, UserInterface $account, $force = FALSE);

}
