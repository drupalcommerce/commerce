<?php

namespace Drupal\commerce_payment;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;

/**
 * Defines the interface for payment gateway storage.
 */
interface PaymentGatewayStorageInterface extends ConfigEntityStorageInterface {

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
