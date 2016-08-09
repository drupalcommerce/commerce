<?php

namespace Drupal\commerce_payment;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Defines the payment method storage.
 */
class PaymentGatewayStorage extends ConfigEntityStorage implements PaymentGatewayStorageInterface {

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
