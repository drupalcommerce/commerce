<?php

namespace Drupal\commerce_payment;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\user\UserInterface;

/**
 * Defines the interface for payment gateway storage.
 */
interface PaymentGatewayStorageInterface extends ConfigEntityStorageInterface {

  /**
   * Loads the default payment gateway for the given user.
   *
   * Used primarily when adding payment methods from the user pages.
   * Thus, only payment gateways which support storing payment methods
   * are considered.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentGatewayInterface
   *   The payment gateway.
   */
  public function loadForUser(UserInterface $account);

  /**
   * Loads all eligible payment gateways for the given order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentGatewayInterface[]
   *   The payment gateways.
   */
  public function loadMultipleForOrder(OrderInterface $order);

}
