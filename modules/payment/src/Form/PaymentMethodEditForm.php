<?php

namespace Drupal\commerce_payment\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the payment method edit form.
 */
class PaymentMethodEditForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['payment_method'] = [
      '#type' => 'commerce_payment_gateway_form',
      '#operation' => 'edit-payment-method',
      '#default_value' => $this->entity,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $this->entity = $form_state->getValue('payment_method');
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // The entity was saved by the plugin form. Redirect.
    drupal_set_message($this->t('Saved the %label @entity-type.', [
      '%label' => $this->entity->label(),
      '@entity-type' => $this->entity->getEntityType()->getLowercaseLabel(),
    ]));
    $form_state->setRedirect($this->entity->toUrl('collection'));
  }

}
