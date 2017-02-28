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
   *
   * @return \Drupal\commerce_payment\Entity\PaymentMethodInterface[]
   *   The reusable payment methods.
   */
  public function loadReusable(UserInterface $account, PaymentGatewayInterface $payment_gateway);

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
