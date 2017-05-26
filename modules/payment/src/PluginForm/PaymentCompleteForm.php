<?php

namespace Drupal\commerce_payment\PluginForm;

use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;

class PaymentCompleteForm extends PaymentCaptureForm {

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    $amount = new Price($values['amount']['number'], $values['amount']['currency_code']);
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsManualWorkflowInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $this->plugin;
    $payment_gateway_plugin->completePayment($payment, $amount);
  }

}
