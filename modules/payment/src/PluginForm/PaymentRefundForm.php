<?php

namespace Drupal\commerce_payment\PluginForm;

use Drupal\Core\Form\FormStateInterface;

class PaymentRefundForm extends PaymentGatewayFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    $form['#success_message'] = t('Payment refunded.');
    $form['amount'] = [
      '#type' => 'commerce_price',
      '#title' => t('Amount'),
      '#default_value' => $payment->getBalance(),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_price\Price $amount */
    $amount = $form['amount']['#value'];
    $balance = $payment->getBalance();
    if ($amount->greaterThan($balance)) {
      $form_state->setError($form['amount'], t("Can't refund more than @amount.", ['@amount' => $balance->__toString()]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $this->plugin;
    $payment_gateway_plugin->refundPayment($payment, $form['amount']['#value']);
  }

}
