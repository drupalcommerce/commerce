<?php

namespace Drupal\commerce_payment\Controller;

use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsNotificationsInterface;
use Drupal\Core\Access\AccessException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides the endpoint for payment notifications.
 */
class PaymentNotificationController {

  /**
   * Provides the "notify" page.
   *
   * Also called the "IPN", "status", "webhook" page by payment providers.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentGatewayInterface $commerce_payment_gateway
   *   The payment gateway.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function notifyPage(PaymentGatewayInterface $commerce_payment_gateway, Request $request) {
    $payment_gateway_plugin = $commerce_payment_gateway->getPlugin();
    if (!$payment_gateway_plugin instanceof SupportsNotificationsInterface) {
      throw new AccessException('Invalid payment gateway provided.');
    }

    $response = $payment_gateway_plugin->onNotify($request);
    if (!$response) {
      $response = new Response('', 200);
    }

    return $response;
  }

}
