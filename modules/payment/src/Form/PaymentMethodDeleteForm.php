<?php

namespace Drupal\commerce_payment\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the payment method delete form.
 */
class PaymentMethodDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->getEntity();
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsStoredPaymentMethods $payment_gateway_plugin */
    $payment_gateway_plugin = $payment_method->getPaymentGateway()->getPlugin();
    try {
      $payment_gateway_plugin->deletePaymentMethod($payment_method);
    }
    catch (\Exception $e) {
      // Now what? Just set a message?
    }

    $form_state->setRedirectUrl($this->getRedirectUrl());
    drupal_set_message($this->getDeletionMessage());
    $this->logDeletionMessage();
  }

}