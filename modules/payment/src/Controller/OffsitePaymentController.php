<?php

namespace Drupal\commerce_payment\Controller;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsCreatingStoredPaymentMethodsOffsiteInterface;
use Drupal\commerce\Response\NeedsRedirectException;
use Drupal\Core\Access\AccessException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides endpoints for off-site payments.
 */
class OffsitePaymentController {

  /**
   * Provides the "return" checkout payment page.
   *
   * Redirects to the next checkout page, completing checkout.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $commerce_order
   *   The order.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   */
  public function returnCheckoutPage(OrderInterface $commerce_order, Request $request) {
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $commerce_order->payment_gateway->entity;
    $payment_gateway_plugin = $payment_gateway->getPlugin();
    if (!$payment_gateway_plugin instanceof OffsitePaymentGatewayInterface) {
      throw new AccessException('The payment gateway for the order does not implement ' . OffsitePaymentGatewayInterface::class);
    }
    /** @var \Drupal\commerce_checkout\Entity\CheckoutFlowInterface $checkout_flow */
    $checkout_flow = $commerce_order->checkout_flow->entity;
    $checkout_flow_plugin = $checkout_flow->getPlugin();

    try {
      $payment_gateway_plugin->onReturn($commerce_order, $request);
      $redirect_step = $checkout_flow_plugin->getNextStepId();
    }
    catch (PaymentGatewayException $e) {
      \Drupal::logger('commerce_payment')->error($e->getMessage());
      drupal_set_message(t('Payment failed at the payment server. Please review your information and try again.'), 'error');
      $redirect_step = $checkout_flow_plugin->getPreviousStepId();
    }
    $checkout_flow_plugin->redirectToStep($redirect_step);
  }

  /**
   * Provides the "cancel" checkout payment page.
   *
   * Redirects to the previous checkout page.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $commerce_order
   *   The order.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   */
  public function cancelCheckoutPage(OrderInterface $commerce_order, Request $request) {
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $commerce_order->payment_gateway->entity;
    $payment_gateway_plugin = $payment_gateway->getPlugin();
    if (!$payment_gateway_plugin instanceof OffsitePaymentGatewayInterface) {
      throw new AccessException('The payment gateway for the order does not implement ' . OffsitePaymentGatewayInterface::class);
    }

    $payment_gateway_plugin->onCancel($commerce_order, $request);
    /** @var \Drupal\commerce_checkout\Entity\CheckoutFlowInterface $checkout_flow */
    $checkout_flow = $commerce_order->checkout_flow->entity;
    $checkout_flow_plugin = $checkout_flow->getPlugin();
    $checkout_flow_plugin->redirectToStep($checkout_flow_plugin->getPreviousStepId());
  }

  /**
   * Provides the "return" page for offsite stored payment method creation.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentGatewayInterface $commerce_payment_gateway
   *   The payment gateway.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @throws \Drupal\Core\Access\AccessException
   * @throws \Drupal\commerce\Response\NeedsRedirectException
   */
  public function returnPage(PaymentGatewayInterface $commerce_payment_gateway, Request $request) {
    $payment_gateway_plugin = $commerce_payment_gateway->getPlugin();
    if (!$payment_gateway_plugin instanceof SupportsCreatingStoredPaymentMethodsOffsiteInterface) {
      throw new AccessException('Invalid payment gateway provided.');
    }

    $payment_gateway_plugin->onCreatePaymentMethod($request);

    $redirect_url = Url::fromRoute('entity.commerce_payment_method.collection', ['user' => \Drupal::currentUser()->id()])->toString();
    throw new NeedsRedirectException($redirect_url);
  }

  /**
   * Provides the "notify" page.
   *
   * Called the "IPN" or "status" page by some payment providers.
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
    if (!$payment_gateway_plugin instanceof OffsitePaymentGatewayInterface) {
      throw new AccessException('Invalid payment gateway provided.');
    }

    $response = $payment_gateway_plugin->onNotify($request);
    if (!$response) {
      $response = new Response('', 200);
    }

    return $response;
  }

}
