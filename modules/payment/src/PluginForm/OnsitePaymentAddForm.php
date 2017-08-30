<?php

namespace Drupal\commerce_payment\PluginForm;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\Core\Form\FormStateInterface;

class OnsitePaymentAddForm extends PaymentGatewayFormBase {

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

    // @todo Implement a balance method (unpaid portion of the total).
    $form['amount'] = [
      '#type' => 'commerce_price',
      '#title' => t('Amount'),
      '#default_value' => $order->getTotalPrice()->toArray(),
      '#required' => TRUE,
    ];
    $form['transaction_type'] = [
      '#type' => 'radios',
      '#title' => t('Transaction type'),
      '#title_display' => 'invisible',
      '#options' => [
        'authorize' => t('Authorize only'),
        'capture' => t('Authorize and capture'),
      ],
      '#default_value' => 'capture',
      '#access' => $this->plugin instanceof SupportsAuthorizationsInterface,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    $capture = ($values['transaction_type'] == 'capture');
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $payment->amount = $values['amount'];
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $this->plugin;
    $payment_gateway_plugin->createPayment($payment, $capture);
  }

}
