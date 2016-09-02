<?php

namespace Drupal\commerce_payment_test\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce\BundleFieldDefinition;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;

/**
 * Provides the credit card payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "dummy",
 *   label = @Translation("Dummy Method"),
 *   create_label = @Translation("New dummy account"),
 * )
 */
class Dummy extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    $args = [
      '@dummy_mail' => $payment_method->dummy_mail->value,
    ];
    return $this->t('Dummy account (@dummy_mail)', $args);
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['dummy_mail'] = BundleFieldDefinition::create('email')
      ->setLabel(t('Dummy Email'))
      ->setDescription(t('The email address associated with the Dummy account.'))
      ->setRequired(TRUE);

    return $fields;
  }

}
