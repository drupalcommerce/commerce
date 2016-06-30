<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines the interface for payment method types.
 */
interface PaymentMethodTypeInterface extends PluginInspectionInterface {

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

  /**
   * Builds the field definitions for payment methods of this type.
   *
   * Important:
   * Field names must be unique across all payment method types.
   * It is recommended to prefix them with the plugin ID.
   *
   * @return \Drupal\commerce\BundleFieldDefinition[]
   *   An array of bundle field definitions, keyed by field name.
   */
  public function buildFieldDefinitions();

}
