<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;

/**
 * Defines the interface for gateways which show payment instructions.
 *
 * Payment instructions are usually shown on checkout complete.
 */
interface HasPaymentInstructionsInterface {

  /**
   * Builds the payment instructions.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   *
   * @return array
   *   A render array containing the payment instructions.
   */
  public function buildPaymentInstructions(PaymentInterface $payment);

}
