<?php

namespace Drupal\commerce_payment;

/**
 * Represents a payment option.
 *
 * @see \Drupal\commerce_payment\PaymentOptionsBuilderInterface::buildOptions()
 */
final class PaymentOption {

  /**
   * The ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The label.
   *
   * @var string
   */
  protected $label;

  /**
   * The payment gateway ID.
   *
   * @var string
   */
  protected $paymentGatewayId;

  /**
   * The payment method ID, when known.
   *
   * @var string
   */
  protected $paymentMethodId;

  /**
   * The payment method type ID, when known.
   *
   * @var string
   */
  protected $paymentMethodTypeId;

  /**
   * Constructs a new PaymentOption object.
   *
   * @param array $definition
   *   The definition.
   */
  public function __construct(array $definition) {
    foreach (['id', 'label', 'payment_gateway_id'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new \InvalidArgumentException(sprintf('Missing required property "%s".', $required_property));
      }
    }

    $this->id = $definition['id'];
    $this->label = $definition['label'];
    $this->paymentGatewayId = $definition['payment_gateway_id'];
    if (isset($definition['payment_method_id'])) {
      $this->paymentMethodId = $definition['payment_method_id'];
    }
    if (isset($definition['payment_method_type_id'])) {
      $this->paymentMethodTypeId = $definition['payment_method_type_id'];
    }
  }

  /**
   * Gets the ID.
   *
   * @return string
   *   The ID.
   */
  public function getId() : string {
    return $this->id;
  }

  /**
   * Gets the label.
   *
   * @return string
   *   The label.
   */
  public function getLabel() : string {
    return $this->label;
  }

  /**
   * Gets the payment gateway ID.
   *
   * @return string
   *   The payment gateway ID.
   */
  public function getPaymentGatewayId() : string {
    return $this->paymentGatewayId;
  }

  /**
   * Gets the payment method ID.
   *
   * Only available when selecting existing payment methods.
   *
   * @return string|null
   *   The payment method ID, or NULL if not known.
   */
  public function getPaymentMethodId() {
    return $this->paymentMethodId;
  }

  /**
   * Gets the payment method type ID.
   *
   * Only available when adding payment methods.
   *
   * @return string|null
   *   The payment method type ID, or NULL if not known.
   */
  public function getPaymentMethodTypeId() {
    return $this->paymentMethodTypeId;
  }

  /**
   * Gets the array representation of the payment option.
   *
   * @return array
   *   The array representation of the payment option.
   */
  public function toArray() : array {
    return [
      'id' => $this->id,
      'label' => $this->label,
      'payment_gateway_id' => $this->paymentGatewayId,
      'payment_method_id' => $this->paymentMethodId,
      'payment_method_type_id' => $this->paymentMethodTypeId,
    ];
  }

}
