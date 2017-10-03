<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentGateway;

use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the interface for gateways which support notifications.
 *
 * Payment providers can use the notification URL (commerce_payment.notify)
 * to inform the site that a new pending/complete payment should be created
 * (if the payment happened off-site), or to provide information about an
 * existing payment (refunds, disputes, etc).
 */
interface SupportsNotificationsInterface {

  /**
   * Processes the notification request.
   *
   * Note:
   * This method can't throw exceptions on failure because some payment
   * providers expect an error response to be returned in that case.
   * Therefore, the method can log the error itself and then choose which
   * response to return.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response|null
   *   The response, or NULL to return an empty HTTP 200 response.
   */
  public function onNotify(Request $request);

}
