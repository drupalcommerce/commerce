<?php

namespace Drupal\commerce_payment;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsStoredPaymentMethodsInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\user\UserInterface;

/**
 * Defines the payment method storage.
 */
class PaymentGatewayStorage extends ConfigEntityStorage implements PaymentGatewayStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadForUser(UserInterface $account) {
    $payment_gateways = $this->loadByProperties(['status' => TRUE]);
    $payment_gateways = array_filter($payment_gateways, function ($payment_gateway) {
      return $payment_gateway->getPlugin() instanceof SupportsStoredPaymentMethodsInterface;
    });
    // @todo Implement resolving logic.
    $payment_gateway = reset($payment_gateways);

    return $payment_gateway;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultipleForOrder(OrderInterface $order) {
    $payment_gateways = $this->loadByProperties(['status' => TRUE]);
    // @todo Invoke the attached conditions to determine eligibility.
    // @todo Fire event.

    return $payment_gateways;
  }

}
