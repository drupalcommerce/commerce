<?php

namespace Drupal\commerce_payment\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the payment capture form.
 */
class PaymentCaptureForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;
    $form['payment'] = [
      '#type' => 'commerce_payment_gateway_form',
      '#operation' => 'capture-payment',
      '#default_value' => $this->entity,
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Capture payment'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('Payment captured.'));
    $form_state->setRedirect('entity.commerce_payment.collection', ['commerce_order' => $this->entity->getOrderId()]);
  }

}
