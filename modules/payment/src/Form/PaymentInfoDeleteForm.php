<?php

/**
 * @file
 * Contains \Drupal\commerce_payment\Form\PaymentInfoDeleteForm.
 */

namespace Drupal\commerce_payment\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a payment information.
 */
class PaymentInfoDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $this->entity->delete();
      $paymentInfoType = $this->entity->payment_method->entity->label();
      $form_state->setRedirectUrl($this->getCancelUrl());
      drupal_set_message($this->t('@type %payment_info_label has been deleted.', array('@type' => $paymentInfoType, '%payment_info_label' => $this->entity->label())));
      $this->logger('commerce_payment')->notice('commerce_payment', '@type: deleted %payment_info_label.', array('@type' => $this->entity->bundle(), '%payment_info_label' => $this->entity->label()));
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('The payment information %payment_info_label could not be deleted.', array('%payment_info_label' => $this->entity->label())), 'error');
      $this->logger('commerce_payment')->error($e);
    }
  }

}
