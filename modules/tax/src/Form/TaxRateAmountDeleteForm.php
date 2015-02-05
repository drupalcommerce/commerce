<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\Form\TaxRateAmountDeleteForm.
 */

namespace Drupal\commerce_tax\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete a tax type.
 */
class TaxRateAmountDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %label?', array('%label' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('entity.commerce_tax_rate_amount.collection', array(
      'commerce_tax_rate' => $this->entity->getRate(),
    ));
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
      drupal_set_message($this->t('Tax rate amount %label has been deleted.', array('%label' => $this->entity->label())));
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('Tax rate amount %label could not be deleted.', array('%label' => $this->entity->label())), 'error');
      $this->logger('commerce_tax')->error($e);
    }
  }

}
