<?php

namespace Drupal\commerce_payment\PluginForm;

use Drupal\Core\Form\FormStateInterface;

class ManualPaymentAddForm extends PaymentGatewayFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $order = $payment->getOrder();
    if (!$order) {
      throw new \InvalidArgumentException('Payment entity with no order reference given to PaymentAddForm.');
    }
    // The payment amount should not exceed the remaining order balance.
    $balance = $order->getBalance();
    $amount = $balance->isPositive() ? $balance : $balance->multiply(0);

    $form['amount'] = [
      '#type' => 'commerce_price',
      '#title' => t('Amount'),
      '#default_value' => $amount->toArray(),
      '#required' => TRUE,
    ];
    $form['received'] = [
      '#type' => 'checkbox',
      '#title' => t('The specified amount was already received.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $payment->amount = $values['amount'];
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\ManualPaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $this->plugin;
    $payment_gateway_plugin->createPayment($payment, $values['received']);
  }

}
