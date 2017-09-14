<?php

namespace Drupal\commerce_payment\Controller;

use Drupal\commerce_checkout\CheckoutOrderManagerInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;
use Drupal\Core\Access\AccessException;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides checkout endpoints for off-site payments.
 */
class PaymentCheckoutController implements ContainerInjectionInterface {

  /**
   * The checkout order manager.
   *
   * @var \Drupal\commerce_checkout\CheckoutOrderManagerInterface
   */
  protected $checkoutOrderManager;

  /**
   * Constructs a new PaymentCheckoutController object.
   *
   * @param \Drupal\commerce_checkout\CheckoutOrderManagerInterface $checkout_order_manager
   *   The checkout order manager.
   */
  public function __construct(CheckoutOrderManagerInterface $checkout_order_manager) {
    $this->checkoutOrderManager = $checkout_order_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_checkout.checkout_order_manager')
    );
  }

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
  public function returnPage(OrderInterface $commerce_order, Request $request) {
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $commerce_order->get('payment_gateway')->entity;
    $payment_gateway_plugin = $payment_gateway->getPlugin();
    if (!$payment_gateway_plugin instanceof OffsitePaymentGatewayInterface) {
      throw new AccessException('The payment gateway for the order does not implement ' . OffsitePaymentGatewayInterface::class);
    }
    /** @var \Drupal\commerce_checkout\Entity\CheckoutFlowInterface $checkout_flow */
    $checkout_flow = $commerce_order->get('checkout_flow')->entity;
    $checkout_flow_plugin = $checkout_flow->getPlugin();
    $step_id = $this->checkoutOrderManager->getCheckoutStepId($commerce_order);

    try {
      $payment_gateway_plugin->onReturn($commerce_order, $request);
      $redirect_step_id = $checkout_flow_plugin->getNextStepId($step_id);
    }
    catch (PaymentGatewayException $e) {
      \Drupal::logger('commerce_payment')->error($e->getMessage());
      drupal_set_message(t('Payment failed at the payment server. Please review your information and try again.'), 'error');
      $redirect_step_id = $checkout_flow_plugin->getPreviousStepId($step_id);
    }
    $checkout_flow_plugin->redirectToStep($redirect_step_id);
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
  public function cancelPage(OrderInterface $commerce_order, Request $request) {
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $commerce_order->get('payment_gateway')->entity;
    $payment_gateway_plugin = $payment_gateway->getPlugin();
    if (!$payment_gateway_plugin instanceof OffsitePaymentGatewayInterface) {
      throw new AccessException('The payment gateway for the order does not implement ' . OffsitePaymentGatewayInterface::class);
    }

    $payment_gateway_plugin->onCancel($commerce_order, $request);
    /** @var \Drupal\commerce_checkout\Entity\CheckoutFlowInterface $checkout_flow */
    $checkout_flow = $commerce_order->get('checkout_flow')->entity;
    $checkout_flow_plugin = $checkout_flow->getPlugin();
    $step_id = $this->checkoutOrderManager->getCheckoutStepId($commerce_order);
    $previous_step_id = $checkout_flow_plugin->getPreviousStepId($step_id);
    $checkout_flow_plugin->redirectToStep($previous_step_id);
  }

}
