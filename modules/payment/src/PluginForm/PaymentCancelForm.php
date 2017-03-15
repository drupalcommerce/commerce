<?php

namespace Drupal\commerce_payment\PluginForm;

use Drupal\Core\Form\FormStateInterface;

class PaymentCancelForm extends PaymentGatewayFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    $form['#theme'] = 'confirm_form';
    $form['#attributes']['class'][] = 'confirmation';
    $form['#page_title'] = t('Are you sure you want to cancel the %label payment?', [
      '%label' => $payment->label(),
    ]);
    $form['#success_message'] = t('Payment canceled.');
    $form['description'] = [
      '#markup' => t('This action cannot be undone.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsManualWorkflowInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $this->plugin;
    $payment_gateway_plugin->cancelPayment($payment);
  }

}
