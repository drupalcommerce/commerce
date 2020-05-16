<?php

namespace Drupal\commerce_payment;

use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\UserInterface;

/**
 * Defines the interface for payment method storage.
 */
interface PaymentMethodStorageInterface extends ContentEntityStorageInterface {

  /**
   * Loads the user's reusable payment methods for the given payment gateway.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account.
   * @param \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway
   *   The payment gateway.
   * @param array $billing_countries
   *   (Optional) A list of billing countries to filter by.
   *   For example, if ['US', 'FR'] is given, only payment methods
   *   with billing profiles from those countries will be returned.
   *   Filtering is skipped if the payment gateway doesn't collect
   *   billing information.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentMethodInterface[]
   *   The reusable payment methods.
   */
  public function loadReusable(UserInterface $account, PaymentGatewayInterface $payment_gateway, array $billing_countries = []);

  /**
   * Constructs a payment method for a customer, without permanently saving it.
   *
   * @param string $payment_method_type
   *   The payment method type.
   * @param string $payment_gateway_id
   *   The payment gateway ID.
   * @param string|int $customer_id
   *   The customer ID.
   * @param \Drupal\profile\Entity\ProfileInterface $billing_profile
   *   The billing profile, optional.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentMethodInterface
   *   A new payment method object.
   */
  public function createForCustomer($payment_method_type, $payment_gateway_id, $customer_id, ProfileInterface $billing_profile = NULL);

}
