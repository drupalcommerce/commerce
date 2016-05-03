<?php

namespace Drupal\commerce_cart;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\user\UserInterface;

/**
 * Handles assigning anonymous cart orders to user accounts.
 *
 * Invoked on login.
 */
interface CartAssignmentInterface {

  /**
   * Assigns all anonymous cart orders to the given user account.
   *
   * The anonymous cart orders are retrieved from the cart session.
   *
   * @param \Drupal\user\UserInterface $account
   *   The account.
   */
  public function assignAll(UserInterface $account);

  /**
   * Assigns the anonymous cart order to the given user account.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $cart
   *   The cart order.
   * @param \Drupal\user\UserInterface $account
   *   The user account.
   */
  public function assign(OrderInterface $cart, UserInterface $account);

}
