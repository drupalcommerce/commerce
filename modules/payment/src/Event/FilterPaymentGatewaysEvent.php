<?php

namespace Drupal\commerce_payment\Event;

use Drupal\commerce_order\Entity\OrderInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the event for filtering the available payment gateways.
 *
 * @see \Drupal\commerce_payment\Event\PaymentEvents
 */
class FilterPaymentGatewaysEvent extends Event {

  /**
   * The payment gateways.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface[]
   */
  protected $paymentGateways;

  /**
   * The order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Constructs a new FilterPaymentGatewaysEvent object.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentGatewayInterface[] $payment_gateways
   *   The payment gateways.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  public function __construct(array $payment_gateways, OrderInterface $order) {
    $this->paymentGateways = $payment_gateways;
    $this->order = $order;
  }

  /**
   * Gets the payment gateways.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentGatewayInterface[]
   *   The payment gateways.
   */
  public function getPaymentGateways() {
    return $this->paymentGateways;
  }

  /**
   * Sets the payment gateways.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentGatewayInterface[] $payment_gateways
   *   The payment gateways.
   *
   * @return $this
   */
  public function setPaymentGateways(array $payment_gateways) {
    $this->paymentGateways = $payment_gateways;
    return $this;
  }

  /**
   * Gets the order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The order.
   */
  public function getOrder() {
    return $this->order;
  }

}
