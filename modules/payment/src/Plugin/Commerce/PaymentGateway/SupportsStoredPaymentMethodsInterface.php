<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentMethodInterface;

/**
 * Defines the interface for gateways which support storing payment methods.
 */
interface SupportsStoredPaymentMethodsInterface {

  /**
   * Deletes the given payment method.
   *
   * Both the entity and the remote record are deleted.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method
   *   The payment method.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   Thrown when the transaction fails for any reason.
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method);

}
