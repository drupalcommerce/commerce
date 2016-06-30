<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentMethodInterface;

/**
 * Defines the interface for gateways which support storing payment methods.
 */
interface SupportsStoredPaymentMethods {

  /**
   * Creates a payment method with the given payment details.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method
   *   The payment method.
   * @param array $payment_details
   *   The gateway-specific payment details.
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details);

  /**
   * Deletes the given payment method.
   *
   * Both the entity and the remote record are deleted.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method
   *   The payment method.
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method);

}
