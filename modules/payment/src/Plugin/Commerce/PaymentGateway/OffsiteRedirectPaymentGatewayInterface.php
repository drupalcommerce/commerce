<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentGateway;

/**
 * Defines the base interface for off-site payment gateways.
 */
interface OffsiteRedirectPaymentGatewayInterface extends OffsitePaymentGatewayInterface {

  /**
   * Gets the off-site redirect URL.
   *
   * If this is TRUE, a form wrapper and JavaScript snippet will be added that
   * submits the off-site payment form, causing a redirect to the payment
   * gateway's payment page.
   *
   * @return bool
   *   Whether to automatically redirect or not.
   */
  public function getRedirectUrl();

}
