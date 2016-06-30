<?php

namespace Drupal\commerce_payment;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\Core\Entity\EntityStorageException;

/**
 * Defines the payment storage.
 */
class PaymentStorage extends CommerceContentEntityStorage {

  /**
   * {@inheritdoc}
   */
  protected function doCreate(array $values) {
    if (!isset($values['payment_gateway'])) {
      throw new EntityStorageException('Missing "payment_gateway" property when creating a payment.');
    }
    // Populate the type using the payment gateway.
    if (!isset($values['type'])) {
      $payment_gateway = $values['payment_gateway'];
      if ($payment_gateway) {
        // The caller passed tha payment gateway ID, load the full entity.
        $payment_gateway_storage = $this->entityManager->getStorage('commerce_payment_gateway');
        /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
        $payment_gateway = $payment_gateway_storage->load($payment_gateway);
      }
      $payment_type = $payment_gateway->getPlugin()->getPaymentType();
      $values['type'] = $payment_type->getPluginId();
    }

    return parent::doCreate($values);
  }

}
