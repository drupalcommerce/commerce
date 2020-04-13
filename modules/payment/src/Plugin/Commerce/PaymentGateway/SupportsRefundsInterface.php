<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_price\Price;

/**
 * Defines the interface for gateways which support refunds.
 */
interface SupportsRefundsInterface {

  /**
   * Checks whether the given payment can be refunded.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment to refund.
   *
   * @return bool
   *   TRUE if the payment can be refunded, FALSE otherwise.
   */
  public function canRefundPayment(PaymentInterface $payment);

  /**
   * Refunds the given payment.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment to refund.
   * @param \Drupal\commerce_price\Price $amount
   *   The amount to refund. If NULL, defaults to the entire payment amount.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   Thrown when the transaction fails for any reason.
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL);

}
