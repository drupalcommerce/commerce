<?php

/**
 * @file
 * Contains Drupal\commerce\Form\CommerceStoreTypeForm.
 */

namespace Drupal\commerce\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

class CommerceStoreTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $store_type = $this->entity;

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $store_type->label(),
      '#description' => $this->t('Label for the store type.'),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $store_type->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\commerce\Entity\CommerceStoreType::load',
      ),
      '#disabled' => !$store_type->isNew(),
    );
    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $store_type->getDescription(),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    try {
      $this->entity->save();
      drupal_set_message($this->t('Saved the %label store type.', array(
        '%label' => $this->entity->label(),
      )));
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('The store type could not be saved.'), 'error');
      $this->logger('commerce')->error($e);
    }
    $form_state->setRedirect('entity.commerce_store_type.list');
  }

}
