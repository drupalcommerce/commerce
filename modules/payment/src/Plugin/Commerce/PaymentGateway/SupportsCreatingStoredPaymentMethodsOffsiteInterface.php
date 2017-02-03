<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentGateway;

use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the interface for gateways which support storing payment methods.
 *
 * The interface should be used for offsite gateways which support "stand alone"
 * creation of stored payment methods (outside of the checkout process).
 *
 * It should accompany main SupportsCreatingStoredPaymentMethodsInterface,
 * which provides a method to initialize the offsite redirect.
 */
interface SupportsCreatingStoredPaymentMethodsOffsiteInterface {

  /**
   * Processes the "return" request from offsite payment method creation.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response|null
   *   The response, or NULL to return an empty HTTP 200 response.
   */
  public function onCreatePaymentMethod(Request $request);

}
