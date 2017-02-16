<?php

namespace Drupal\commerce_payment\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the payment operation form.
 */
class PaymentOperationForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $operations = $payment_gateway_plugin->buildPaymentOperations($payment);
    $operation_id = $this->getRouteMatch()->getParameter('operation');
    $operation = $operations[$operation_id];

    $form['#title'] = $operation['page_title'];
    $form['#tree'] = TRUE;
    $form['payment'] = [
      '#type' => 'commerce_payment_gateway_form',
      '#operation' => $operation['plugin_form'],
      '#default_value' => $this->entity,
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $operation['title'],
      '#button_type' => 'primary',
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#attributes' => ['class' => ['button']],
      '#url' => $this->entity->toUrl('collection'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!empty($form['payment']['#success_message'])) {
      drupal_set_message($form['payment']['#success_message']);
    }
    $form_state->setRedirect('entity.commerce_payment.collection', ['commerce_order' => $this->entity->getOrderId()]);
  }

}
