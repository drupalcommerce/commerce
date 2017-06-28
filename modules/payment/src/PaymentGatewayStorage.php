<?php

namespace Drupal\commerce_payment;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsStoredPaymentMethodsInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\user\UserInterface;

/**
 * Defines the payment gateway storage.
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
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface[] $payment_gateways */
    $payment_gateways = $this->loadByProperties(['status' => TRUE]);
    foreach ($payment_gateways as $payment_gateway_id => $payment_gateway) {
      if (!$payment_gateway->applies($order)) {
        unset($payment_gateways[$payment_gateway_id]);
      }
    }
    uasort($payment_gateways, [$this->entityType->getClass(), 'sort']);
    // @todo Fire event.

    return $payment_gateways;
  }

}
