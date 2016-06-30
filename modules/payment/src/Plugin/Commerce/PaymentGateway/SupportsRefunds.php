<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_price\Price;

/**
 * Defines the interface for gateways which support refunds.
 */
interface SupportsRefunds {

  /**
   * Refunds the given payment.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment to refund.
   * @param \Drupal\commerce_price\Price $amount
   *   The amount to refund. If NULL, defaults to the entire payment amount.
   */
  public function refund(PaymentInterface $payment, Price $amount = NULL);

}
