<?php

/**
 * @file
 * Contains Drupal\commerce\Form\CommerceStoreForm.
 */

namespace Drupal\commerce\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;

/**
 * Form controller for the store edit form.
 */
class CommerceStoreForm extends ContentEntityForm {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\commerce\Entity\CommerceStore */
    $form = parent::form($form, $form_state);
    $entity = $this->entity;

    $form['langcode'] = array(
      '#title' => t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->getUntranslated()->language()->id,
      '#languages' => Language::STATE_ALL,
    );
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Store name'),
      '#default_value' => $entity->getName(),
      '#required' => TRUE,
    );
    $form['mail'] = array(
      '#type' => 'email',
      '#title' => $this->t('E-mail address'),
      '#description' => $this->t('A valid e-mail address. Store e-mail notifications will be sent to and from this address.'),
      '#default_value' => $entity->getEmail(),
      '#required' => TRUE,
    );
    $form['default_currency'] = array(
      '#type' => 'select',
      '#title' => t('Default currency'),
      '#options' => array('EUR' => 'EUR', 'GBP' => 'GBP', 'USD' => 'USD'),
    );

    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, FormStateInterface $form_state) {
    try {
      $this->entity->save();
      drupal_set_message($this->t('Saved the %label store.', array(
        '%label' => $this->entity->label(),
      )));
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('The store could not be saved.'), 'error');
      $this->logger('commerce')->error($e);
    }
    $form_state->setRedirect('entity.commerce_store.list');
  }

}
