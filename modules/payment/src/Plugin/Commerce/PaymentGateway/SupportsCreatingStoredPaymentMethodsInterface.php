<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentMethodInterface;

/**
 * Defines the interface for gateways which support storing payment methods.
 *
 * The interface should be used for gateways (both onsite and offsite) which
 * support "stand alone" creation of stored payment methods (outside of the
 * checkout process).
 *
 * Offsite gateways should implement additionally
 * SupportsCreatingStoredPaymentMethodsOffsiteInterface, which provides method
 * to handle user return from the offsite redirect.
 */
interface SupportsCreatingStoredPaymentMethodsInterface {

  /**
   * Creates a payment method with the given payment details.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method
   *   The payment method.
   * @param array $payment_details
   *   The gateway-specific payment details.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   Thrown when the transaction fails for any reason.
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details);

}
