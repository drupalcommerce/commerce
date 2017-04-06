<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce\BundlePluginInterface;
use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;

/**
 * Defines the interface for payment method types.
 */
interface PaymentMethodTypeInterface extends BundlePluginInterface {

  /**
   * Gets the payment method type label.
   *
   * @return string
   *   The payment method type label.
   */
  public function getLabel();

  /**
   * Gets the payment method type create label.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway
   *   The payment gateway.
   *
   * @return string
   *   The payment method type create label.
   */
  public function getCreateLabel(PaymentGatewayInterface $payment_gateway);

  /**
   * Builds a label for the given payment method.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method
   *   The payment method.
   *
   * @return string
   *   The label.
   */
  public function buildLabel(PaymentMethodInterface $payment_method);

}
