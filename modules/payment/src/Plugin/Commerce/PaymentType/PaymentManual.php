<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentType;

/**
 * Provides the manual payment type.
 *
 * @CommercePaymentType(
 *   id = "payment_manual",
 *   label = @Translation("Manual"),
 *   workflow = "payment_manual",
 * )
 */
class PaymentManual extends PaymentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    return [];
  }

}
