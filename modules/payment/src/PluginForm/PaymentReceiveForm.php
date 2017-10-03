<?php

namespace Drupal\commerce_payment\PluginForm;

use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;

class PaymentReceiveForm extends PaymentGatewayFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    $form['#success_message'] = t('Payment received.');
    $form['amount'] = [
      '#type' => 'commerce_price',
      '#title' => t('Amount'),
      '#default_value' => $payment->getAmount()->toArray(),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    $amount = new Price($values['amount']['number'], $values['amount']['currency_code']);
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\ManualPaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $this->plugin;
    $payment_gateway_plugin->receivePayment($payment, $amount);
  }

}
