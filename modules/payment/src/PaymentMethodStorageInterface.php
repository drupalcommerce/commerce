<?php

namespace Drupal\commerce_payment;

use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\user\UserInterface;
use Drupal\profile\Entity\ProfileInterface;

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
   *
   * @return \Drupal\commerce_payment\Entity\PaymentMethodInterface[]
   *   The reusable payment methods.
   */
  public function loadReusable(UserInterface $account, PaymentGatewayInterface $payment_gateway, array $billing_countries = []);

  /**
   * Loads the payment methods for a billing profile and payment gateway.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The billing profile.
   * @param \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway
   *   The payment gateway.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentMethodInterface[]
   *   The payment methods for the given billing profile and payment gateway.
   */
  public function loadForProfile(ProfileInterface $profile, PaymentGatewayInterface $payment_gateway);

}
