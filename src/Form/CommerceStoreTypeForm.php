<?php

/**
 * @file
 * Contains Drupal\commerce\Form\CommerceStoreTypeForm.
 */

namespace Drupal\commerce\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

class CommerceStoreTypeForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $commerce_store_type = $this->entity;

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $commerce_store_type->label(),
      '#description' => $this->t('Label for the store type.'),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $commerce_store_type->id(),
      '#machine_name' => array(
        'exists' => 'commerce_store_type_load',
      ),
      '#disabled' => !$commerce_store_type->isNew(),
    );

    // You will need additional form elements for your custom properties.
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
      watchdog_exception('commerce', $e);
    }
    $form_state['redirect_route']['route_name'] = 'commerce.store_type_list';
  }
}
