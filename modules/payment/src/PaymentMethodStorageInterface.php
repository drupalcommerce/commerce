<?php

namespace Drupal\commerce_payment;

use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\commerce_store\Entity\StoreInterface;
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
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   An optional store to filter by, for available billing countries.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentMethodInterface[]
   *   The reusable payment methods.
   */
  public function loadReusable(UserInterface $account, PaymentGatewayInterface $payment_gateway, StoreInterface $store = NULL);

}
