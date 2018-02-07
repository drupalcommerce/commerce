<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_price\Price;

/**
 * Provides the base interface for manual payment gateways.
 *
 * Manual payment gateways instruct the customer to pay the store
 * in the real world. The gateway creates a payment entity to allow
 * the merchant to track and record the money flow.
 *
 * Examples: cash on delivery, pay in person, cheque, bank transfer, etc.
 */
interface ManualPaymentGatewayInterface extends PaymentGatewayInterface, HasPaymentInstructionsInterface, SupportsVoidsInterface, SupportsRefundsInterface {

  /**
   * Creates a payment.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   * @param bool $received
   *   Whether the payment was already received.
   */
  public function createPayment(PaymentInterface $payment, $received = FALSE);

  /**
   * Receives the given payment.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   * @param \Drupal\commerce_price\Price $amount
   *   The received amount. If NULL, defaults to the entire payment amount.
   */
  public function receivePayment(PaymentInterface $payment, Price $amount = NULL);

}
