<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentMethodInterface;

/**
 * An interface for gateways supporting direct creation of payment methods.
 *
 * Payment gateways that implement this interface identify that they allow
 * creating payment methods outside of the process of creating a payment. This
 * will allow tokenization of payment methods during checkout before the order
 * is placed, or from the user page.
 */
interface SupportsCreatingPaymentMethodsInterface extends SupportsStoredPaymentMethodsInterface {

  /**
   * Creates a payment method with the given payment details.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method
   *   The payment method.
   * @param array $payment_details
   *   The gateway-specific payment details provided by the payment method form
   *   for on-site gateways, or the incoming request for off-site gateways.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   Thrown when the transaction fails for any reason.
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details);

}
