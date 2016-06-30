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

    return parent::doCreate($values);
  }

}
