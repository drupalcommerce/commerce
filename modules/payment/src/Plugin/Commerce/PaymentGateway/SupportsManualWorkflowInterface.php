<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_price\Price;

/**
 * Defines the interface for gateways which support manual payment workflow.
 */
interface SupportsManualWorkflowInterface {

  /**
   * Completes the given pending payment.
   *
   * Only payments in the 'authorization' state can be captured.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment to capture.
   * @param \Drupal\commerce_price\Price $amount
   *   The amount to capture. If NULL, defaults to the entire payment amount.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   Thrown when the transaction fails for any reason.
   */
  public function completePayment(PaymentInterface $payment, Price $amount = NULL);

  /**
   * Voids the given payment.
   *
   * Only payments in the 'pending' state can be voided.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment to cancel.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   Thrown when the transaction fails for any reason.
   */
  public function cancelPayment(PaymentInterface $payment);

}
