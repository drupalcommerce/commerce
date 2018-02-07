<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType;

use Drupal\entity\BundlePlugin\BundlePluginInterface;
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
   * @return string
   *   The payment method type create label.
   */
  public function getCreateLabel();

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
