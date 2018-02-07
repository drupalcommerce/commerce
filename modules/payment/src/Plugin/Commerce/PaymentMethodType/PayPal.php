<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType;

use Drupal\entity\BundleFieldDefinition;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;

/**
 * Provides the PayPal payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "paypal",
 *   label = @Translation("PayPal account"),
 *   create_label = @Translation("New PayPal account"),
 * )
 */
class PayPal extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    $args = [
      '@paypal_mail' => $payment_method->paypal_mail->value,
    ];
    return $this->t('PayPal account (@paypal_mail)', $args);
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['paypal_mail'] = BundleFieldDefinition::create('email')
      ->setLabel(t('PayPal Email'))
      ->setDescription(t('The email address associated with the PayPal account.'))
      ->setRequired(TRUE);

    return $fields;
  }

}
