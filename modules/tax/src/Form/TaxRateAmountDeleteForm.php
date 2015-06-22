<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\Form\TaxRateAmountDeleteForm.
 */

namespace Drupal\commerce_tax\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the form to delete a tax type.
 */
class TaxRateAmountDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $this->entity->delete();
      $form_state->setRedirectUrl($this->getCancelUrl());
      drupal_set_message($this->t('Tax rate amount %label has been deleted.', array('%label' => $this->entity->label())));
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('Tax rate amount %label could not be deleted.', array('%label' => $this->entity->label())), 'error');
      $this->logger('commerce_tax')->error($e);
    }
  }

}
