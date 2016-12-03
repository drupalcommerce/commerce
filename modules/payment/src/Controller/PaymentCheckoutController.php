<?php

namespace Drupal\commerce_payment\Controller;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;
use Drupal\Core\Access\AccessException;

class PaymentCheckoutController {

  /**
   * Controller callback for offsite payments cancelled.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $commerce_order
   *   The order.
   */
  public function cancelPaymentPage(OrderInterface $commerce_order) {
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $commerce_order->payment_gateway->entity;
    $payment_gateway_plugin = $payment_gateway->getPlugin();
    if (!$payment_gateway_plugin instanceof OffsitePaymentGatewayInterface) {
      throw new AccessException('The payment gateway for the order does not implement ' . OffsitePaymentGatewayInterface::class);
    }

    $payment_gateway_plugin->onRedirectCancel($commerce_order);
    /** @var \Drupal\commerce_checkout\Entity\CheckoutFlowInterface $checkout_flow */
    $checkout_flow = $commerce_order->checkout_flow->entity;
    $checkout_flow_plugin = $checkout_flow->getPlugin();
    $checkout_flow_plugin->redirectToStep($checkout_flow_plugin->getPreviousStepId());
  }

  /**
   * Controller callback for offsite payments returned.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $commerce_order
   *   The order.
   */
  public function returnPaymentPage(OrderInterface $commerce_order) {
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $commerce_order->payment_gateway->entity;
    $payment_gateway_plugin = $payment_gateway->getPlugin();
    if (!$payment_gateway_plugin instanceof OffsitePaymentGatewayInterface) {
      throw new AccessException('The payment gateway for the order does not implement ' . OffsitePaymentGatewayInterface::class);
    }

    $payment_gateway_plugin->onRedirectReturn($commerce_order);
    /** @var \Drupal\commerce_checkout\Entity\CheckoutFlowInterface $checkout_flow */
    $checkout_flow = $commerce_order->checkout_flow->entity;
    $checkout_flow_plugin = $checkout_flow->getPlugin();
    $checkout_flow_plugin->redirectToStep($checkout_flow_plugin->getNextStepId());
  }

}
