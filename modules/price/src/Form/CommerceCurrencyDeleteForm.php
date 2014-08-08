<?php

/**
 * @file
 * Contains \Drupal\commerce_price\Form\CommerceCurrencyDeleteForm.
 */

namespace Drupal\commerce_price\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete a currency.
 */
class CommerceCurrencyDeleteForm extends EntityConfirmFormBase {

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
    return new Url('entity.commerce_currency.list');
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
  public function submit(array $form, FormStateInterface $form_state) {
    try {
      $this->entity->delete();
      $form_state->setRedirectUrl($this->getCancelUrl());
      drupal_set_message($this->t('Currency %label has been deleted.', array('%label' => $this->entity->label())));
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('Currency %label could not be deleted.', array('%label' => $this->entity->label())), 'error');
      watchdog_exception('commerce_price', $e);
    }
  }

}
