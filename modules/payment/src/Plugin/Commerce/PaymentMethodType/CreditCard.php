<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType;

use Drupal\entity\BundleFieldDefinition;
use Drupal\commerce_payment\CreditCard as CreditCardHelper;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;

/**
 * Provides the credit card payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "credit_card",
 *   label = @Translation("Credit card"),
 *   create_label = @Translation("New credit card"),
 * )
 */
class CreditCard extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    $card_type = CreditCardHelper::getType($payment_method->card_type->value);
    $args = [
      '@card_type' => $card_type->getLabel(),
      '@card_number' => $payment_method->card_number->value,
    ];
    return $this->t('@card_type ending in @card_number', $args);
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['card_type'] = BundleFieldDefinition::create('list_string')
      ->setLabel(t('Card type'))
      ->setDescription(t('The credit card type.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values_function', ['\Drupal\commerce_payment\CreditCard', 'getTypeLabels']);

    $fields['card_number'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Card number'))
      ->setDescription(t('The last few digits of the credit card number'))
      ->setRequired(TRUE);

    // card_exp_month and card_exp_year are not required because they might
    // not be known (tokenized non-reusable payment methods).
    $fields['card_exp_month'] = BundleFieldDefinition::create('integer')
      ->setLabel(t('Card expiration month'))
      ->setDescription(t('The credit card expiration month.'))
      ->setSetting('size', 'tiny');

    $fields['card_exp_year'] = BundleFieldDefinition::create('integer')
      ->setLabel(t('Card expiration year'))
      ->setDescription(t('The credit card expiration year.'))
      ->setSetting('size', 'small');

    return $fields;
  }

}
