<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;

/**
 * Provides the manual payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "manual",
 *   label = @Translation("manual"),
 *   create_label = @Translation("New manual"),
 * )
 */
class Manual extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    $payment_gateway = $payment_method->getPaymentGateway();
    // Use billing profile address data to identify the payment method.
    if ($billing_profile = $payment_method->getBillingProfile()) {
      /** @var \Drupal\address\AddressInterface $address */
      $address = $billing_profile->address->first();
      $name = $address->getGivenName() . ' ' . $address->getFamilyName();
      $location = $address->getAddressLine1() . ', ' . $address->getLocality();
      $args = [
        '@gateway_title' => $payment_gateway->label(),
        '@name' => $name,
        '@location' => $location,
      ];
      $label = $this->t('@gateway_title for @name (@location)', $args);
    }
    else {
      $args = [
        '@gateway_title' => $payment_gateway->label(),
      ];
      $label = $this->t('Manual - @gateway_title', $args);
    }

    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreateLabel(PaymentGatewayInterface $payment_gateway) {
    return $payment_gateway->label();
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    // Probably the fields for the Offline payments should be done in the UI.
    return [];
  }

}
