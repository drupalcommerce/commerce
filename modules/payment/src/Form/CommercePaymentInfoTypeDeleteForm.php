<?php

/**
 * @file
 * Contains Drupal\commerce_payment\Form\CommercePaymentInfoTypeDeleteForm.
 */

namespace Drupal\commerce_payment\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete an payment information type.
 */
class CommercePaymentInfoTypeDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %payment_info_type_label?', array('%payment_info_type_label' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.commerce_payment_info_type.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

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
