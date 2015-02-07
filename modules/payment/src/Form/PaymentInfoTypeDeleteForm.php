<?php

/**
 * @file
 * Contains Drupal\commerce_payment\Form\PaymentInfoTypeDeleteForm.
 */

namespace Drupal\commerce_payment\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete an payment information type.
 */
class PaymentInfoTypeDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $this->entity->delete();
      $form_state->setRedirectUrl($this->getCancelUrl());
      drupal_set_message($this->t('Payment information type %payment_info_type_label has been deleted.', array('%payment_info_type_label' => $this->entity->label())));
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('Payment information type %payment_info_type_label could not be deleted.', array('%payment_info_type_label' => $this->entity->label())), 'error');
      $this->logger('commerce_payment')->error($e);
    }
  }

}
