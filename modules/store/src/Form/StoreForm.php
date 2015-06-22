<?php

/**
 * @file
 * Contains Drupal\commerce\Form\StoreForm.
 */

namespace Drupal\commerce_store\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the store edit form.
 */
class StoreForm extends ContentEntityForm {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\commerce_store\Entity\Store */
    $form = parent::form($form, $form_state);
    $entity = $this->entity;

    // Add default store config setting.
    $default_store = \Drupal::config('commerce_store.settings')->get('default_store');
    $is_default_store = FALSE;
    $disabled = FALSE;
    if (!empty($default_store) && $default_store == $entity->uuid()) {
      $is_default_store = TRUE;
    }
    if (empty($default_store)) {
      $is_default_store = TRUE;
      $disabled = TRUE;
    }
    $form['is_default_store'] = [
      '#type' => 'checkbox',
      '#title' => t('Default store'),
      '#default_value' => $is_default_store,
      '#disabled' => $disabled,
      '#weight' => 0,
    ];

    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, FormStateInterface $form_state) {
    try {
      $this->entity->save();
      // Save the default store config setting.
      if (!$form_state->isValueEmpty('is_default_store') && $form_state->getValue('is_default_store') != FALSE) {
        \Drupal::configFactory()->getEditable('commerce_store.settings')->set('default_store', $this->entity->uuid())->save();
      }
      drupal_set_message($this->t('Saved the %label store.', [
        '%label' => $this->entity->label(),
      ]));
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('The store could not be saved.'), 'error');
      $this->logger('commerce')->error($e);
    }
    $form_state->setRedirect('entity.commerce_store.collection');
  }

}
