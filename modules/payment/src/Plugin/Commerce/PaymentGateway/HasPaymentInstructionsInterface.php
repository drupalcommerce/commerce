<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentGateway;

/**
 * Defines the interface for gateways which show payment instructions.
 *
 * Payment instructions are usually shown on checkout complete.
 */
interface HasPaymentInstructionsInterface {

  /**
   * Builds the payment instructions.
   *
   * @return array
   *   A render array containing the payment instructions.
   */
  public function buildPaymentInstructions();

}
