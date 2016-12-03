<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Url;

/**
 * Provides the base class for off-site payment gateways.
 */
abstract class OffsitePaymentGatewayBase extends PaymentGatewayBase implements OffsitePaymentGatewayInterface {

  /**
   * {@inheritdoc}
   */
  public function getRedirectCancelUrl(OrderInterface $order) {
    return Url::fromRoute('commerce_payment.checkout.cancel', [
      'commerce_order' => $order->id(),
      'step' => 'payment',
    ], ['absolute' => TRUE]);
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectReturnUrl(OrderInterface $order) {
    return Url::fromRoute('commerce_payment.checkout.return', [
      'commerce_order' => $order->id(),
      'step' => 'payment',
    ], ['absolute' => TRUE]);
  }

}
