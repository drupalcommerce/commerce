<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\Form\TaxTypeDeleteForm.
 */

namespace Drupal\commerce_tax\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete a tax type.
 */
class TaxTypeDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $this->entity->delete();
      $form_state->setRedirectUrl($this->getCancelUrl());
      drupal_set_message($this->t('Tax type %label has been deleted.', array('%label' => $this->entity->label())));
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('Tax type %label could not be deleted.', array('%label' => $this->entity->label())), 'error');
      $this->logger('commerce_tax')->error($e);
    }
  }

}
