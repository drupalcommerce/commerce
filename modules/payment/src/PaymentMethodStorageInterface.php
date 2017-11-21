<?php

namespace Drupal\commerce_payment;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\user\UserInterface;

/**
 * Defines the interface for payment method storage.
 */
interface PaymentMethodStorageInterface extends ContentEntityStorageInterface {

  /**
   * Loads the user's reusable payment methods for the given payment gateway.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account.
   * @param \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway
   *   The payment gateway.
   * @param array $billing_countries
   *   (Optional) A list of billing countries to filter by.
   *   For example, if ['US', 'FR'] is given, only payment methods
   *   with billing profiles from those countries will be returned.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentMethodInterface[]
   *   The reusable payment methods.
   */
  public function loadReusable(UserInterface $account, PaymentGatewayInterface $payment_gateway, array $billing_countries = []);

  /**
   * Loads the order's stored payment methods for the given payment gateway.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway
   *   The payment gateway.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentMethodInterface[]
   *   The stored payment methods.
   */
  public function loadForOrder(OrderInterface $order, PaymentGatewayInterface $payment_gateway);

}
