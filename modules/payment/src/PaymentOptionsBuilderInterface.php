<?php

namespace Drupal\commerce_payment;

use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Builds payment options for an order.
 */
interface PaymentOptionsBuilderInterface {

  /**
   * Builds the payment options for the given order's payment gateways.
   *
   * The payment options will be derived from the given payment gateways
   * in the following order:
   * 1) The customer's stored payment methods.
   * 2) The order's payment method (if not added in the previous step).
   * 3) Options to create new payment methods of valid types.
   * 4) Options for the remaining gateways (off-site, manual, etc).
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Drupal\commerce_payment\Entity\PaymentGatewayInterface[] $payment_gateways
   *   The payment gateways. When empty, defaults to all available gateways.
   *
   * @return \Drupal\commerce_payment\PaymentOption[]
   *   The payment options, keyed by option ID.
   */
  public function buildOptions(OrderInterface $order, array $payment_gateways = []);

  /**
   * Selects the default payment option for the given order.
   *
   * Priority:
   * 1) The order's payment method
   * 2) The order's payment gateway (if it does not support payment methods)
   * 3) First defined option.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param array $options
   *   The options.
   *
   * @return \Drupal\commerce_payment\PaymentOption
   *   The selected option.
   */
  public function selectDefaultOption(OrderInterface $order, array $options);

}
