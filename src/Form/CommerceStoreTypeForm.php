<?php

/**
 * @file
 * Contains Drupal\commerce\Form\CommerceStoreTypeForm.
 */

namespace Drupal\commerce\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;

class CommerceStoreTypeForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
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
  public function save(array $form, array &$form_state) {
    $commerce_store_type = $this->entity;
    $status = $commerce_store_type->save();

    if ($status) {
      drupal_set_message($this->t('Saved the %label store type.', array(
        '%label' => $commerce_store_type->label(),
      )));
    }
    else {
      drupal_set_message($this->t('The %label store type was not saved.', array(
        '%label' => $commerce_store_type->label(),
      )));
    }

    $form_state['redirect'] = 'admin/commerce/config/store/types';
  }
}
