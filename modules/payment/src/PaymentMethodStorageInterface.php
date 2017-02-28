<?php

namespace Drupal\commerce_payment;

use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
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
   *
   * @return \Drupal\commerce_payment\Entity\PaymentMethodInterface[]
   *   The reusable payment methods.
   */
  public function loadReusable(UserInterface $account, PaymentGatewayInterface $payment_gateway, array $billing_countries = []);

  /**
   * Loads the given user's payment methods.
   *
   * @param \Drupal\user\UserInterface $account
   *    The user entity.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentMethodInterface[]
   *    An array of loaded payment methods entities.
   */
  public function loadMultipleByUser(UserInterface $account);

  /**
   * Loads the default user payment method.
   *
   * @param \Drupal\user\UserInterface $account
   *    The user entity.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentMethodInterfaces
   *   The default payment method.
   */
  public function loadDefaultByUser(UserInterface $account);

}
