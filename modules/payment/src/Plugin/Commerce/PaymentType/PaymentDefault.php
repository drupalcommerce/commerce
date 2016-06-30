<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentType;

/**
 * Provides the default payment type.
 *
 * @CommercePaymentType(
 *   id = "payment_default",
 *   label = @Translation("Default"),
 * )
 */
class PaymentDefault extends PaymentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    return [];
  }

}
