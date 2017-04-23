<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;

/**
 * Defines the base interface for payment gateways.
 */
interface PaymentGatewayInterface extends PluginWithFormsInterface, ConfigurablePluginInterface, PluginFormInterface, DerivativeInspectionInterface {

  /**
   * Gets the payment gateway label.
   *
   * The label is admin-facing and usually includes the name of the used API.
   * For example: "Braintree (Hosted Fields)".
   *
   * @return mixed
   *   The payment gateway label.
   */
  public function getLabel();

  /**
   * Gets the payment gateway display label.
   *
   * The display label is customer-facing and more generic.
   * For example: "Braintree".
   *
   * @return string
   *   The payment gateway display label.
   */
  public function getDisplayLabel();

  /**
   * Gets the mode in which the payment gateway is operating.
   *
   * @return string
   *   The machine name of the mode.
   */
  public function getMode();

  /**
   * Gets the supported modes.
   *
   * @return string[]
   *   The mode labels keyed by machine name.
   */
  public function getSupportedModes();

  /**
   * Gets the JS library ID.
   *
   * This is usually an external library defined in the module's
   * libraries.yml file. Included by the PaymentInformation pane
   * to get around core bug #1988968.
   * Example: 'commerce_braintree/braintree'.
   *
   * @return string|null
   *   The JS library ID, or NULL if not available.
   */
  public function getJsLibrary();

  /**
   * Gets the payment type used by the payment gateway.
   *
   * @return \Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeInterface
   *   The payment type.
   */
  public function getPaymentType();

  /**
   * Gets the payment method types handled by the payment gateway.
   *
   * @return \Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeInterface[]
   *   The payment method types.
   */
  public function getPaymentMethodTypes();

  /**
   * Gets the default payment method type.
   *
   * @return \Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeInterface
   *   The default payment method type.
   */
  public function getDefaultPaymentMethodType();

  /**
   * Gets the credit card types handled by the gateway.
   *
   * @return \Drupal\commerce_payment\CreditCardType[]
   *   The credit card types.
   */
  public function getCreditCardTypes();

  /**
   * Builds the available operations for the given payment.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   *
   * @return array
   *   The operations.
   *   Keyed by operation ID, each value is an array with the following keys:
   *   - title: The operation title.
   *   - page_title: The operation page title.
   *   - plugin_form: The plugin form ID.
   *   - access: Whether the operation is allowed for the given payment.
   */
  public function buildPaymentOperations(PaymentInterface $payment);

}
