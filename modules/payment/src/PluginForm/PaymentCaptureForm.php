<?php

namespace Drupal\commerce_payment\PluginForm;

use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;

class PaymentCaptureForm extends PaymentGatewayFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $amount = $payment->getAmount();

    $form['#success_message'] = t('Payment captured.');
    $form['amount'] = [
      '#type' => 'commerce_price',
      '#title' => t('Amount'),
      '#default_value' => $amount->toArray(),
      '#required' => TRUE,
      '#available_currencies' => [$amount->getCurrencyCode()],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    $amount = Price::fromArray($values['amount']);
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $this->plugin;
    $payment_gateway_plugin->capturePayment($payment, $amount);
  }

}
