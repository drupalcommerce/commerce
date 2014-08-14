<?php
/**
 * @file
 * Definition of Drupal\commerce_payment\Form\CommercePaymentInfoForm.
 */

namespace Drupal\commerce_payment\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the commerce_payment_info entity edit forms.
 */
class CommercePaymentInfoForm extends ContentEntityForm {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, FormStateInterface $form_state) {
    try {
      $this->entity->save();
      drupal_set_message($this->t('The payment information %payment_info_label has been successfully saved.', array('%payment_info_label' => $this->entity->label())));
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('The payment information %payment_info_label could not be saved.', array('%payment_info_label' => $this->entity->label())), 'error');
      watchdog_exception('commerce_payment', $e);
    }
    $form_state->setRedirect('entity.commerce_payment_info.list');
  }

}
