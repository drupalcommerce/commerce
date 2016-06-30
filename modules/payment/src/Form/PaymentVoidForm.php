<?php

namespace Drupal\commerce_payment\Form;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the payment void form.
 */
class PaymentVoidForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to void the %label payment?', [
      '%label' => $this->getEntity()->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Void');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    $entity = $this->getEntity();
    return $this->t('The %label payment has been voided.', [
      '%label' => $entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->getEntity();
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $form_state->setRedirectUrl($this->getRedirectUrl());
    try {
      $payment_gateway_plugin->voidPayment($payment);
    }
    catch (PaymentGatewayException $e) {
      drupal_set_message($e->getMessage(), 'error');
      return;
    }

    drupal_set_message($this->getDeletionMessage());
    $this->logDeletionMessage();
  }

}
