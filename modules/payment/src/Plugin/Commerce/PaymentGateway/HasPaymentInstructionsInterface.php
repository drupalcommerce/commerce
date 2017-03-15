<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentGateway;

/**
 * Defines the interface for gateways which support payment methods with
 * instructions.
 */
interface HasPaymentInstructionsInterface {

  /**
   * Creates a payment method with the given payment instructions.
   *
   * @return array|NULL
   *   A renderable array containing payment instructions or NULL.
   */
  public function getPaymentInstructions();

}
