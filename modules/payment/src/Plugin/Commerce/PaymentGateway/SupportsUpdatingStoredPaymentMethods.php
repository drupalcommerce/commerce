<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentMethodInterface;

/**
 * Defines the interface for gateways which support updating stored payment methods.
 */
interface SupportsUpdatingStoredPaymentMethods {

  /**
   * Updates the given payment.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method
   *   The payment method.
   */
  public function updatePaymentMethod(PaymentMethodInterface $payment_method);

}
