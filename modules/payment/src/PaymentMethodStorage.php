<?php

namespace Drupal\commerce_payment;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsStoredPaymentMethodsInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\user\UserInterface;

/**
 * Defines the payment method storage.
 */
class PaymentMethodStorage extends CommerceContentEntityStorage implements PaymentMethodStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadReusable(UserInterface $account, PaymentGatewayInterface $payment_gateway) {
    if (!($payment_gateway->getPlugin() instanceof SupportsStoredPaymentMethodsInterface)) {
      return [];
    }

    $query = $this->getQuery()
      ->condition('uid', $account->id())
      ->condition('payment_gateway', $payment_gateway->id())
      ->condition('reusable', TRUE)
      ->sort('created', 'DESC');
    $result = $query->execute();
    if (empty($result)) {
      return [];
    }

    return $this->loadMultiple($result);
  }

  /**
   * {@inheritdoc}
   */
  protected function doCreate(array $values) {
    if (!isset($values['payment_gateway'])) {
      throw new EntityStorageException('Missing "payment_gateway" property when creating a payment method.');
    }

    return parent::doCreate($values);
  }

}
