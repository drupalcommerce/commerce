<?php

/**
 * @file
 * Contains Drupal\commerce\Form\CommerceStoreDeleteForm
 */

namespace Drupal\commerce\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a store.
 */
class CommerceStoreDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the store %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.commerce_store.list');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, FormStateInterface $form_state) {
    try {
      $this->entity->delete();
      drupal_set_message($this->t('Store %label has been deleted.', array('%label' => $this->entity->label())));
      $this->logger('commerce')->notice('Store %name has been deleted.', array('%label' => $this->entity->label()));
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('Store %label could not be deleted.', array('%label' => $this->entity->label()), 'error'));
      $this->logger('commerce')->error($e);
    }
    $form_state->setRedirect($this->getCancelUrl());
  }

}
