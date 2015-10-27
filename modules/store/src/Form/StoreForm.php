<?php

/**
 * @file
 * Contains \Drupal\commerce\Form\StoreForm.
 */

namespace Drupal\commerce_store\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the store edit form.
 */
class StoreForm extends ContentEntityForm {

  /**
   * The store storage.
   *
   * @var \Drupal\commerce_store\StoreStorageInterface
   */
  protected $storage;

  /**
   * Constructs a new StoreForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entityManager) {
    parent::__construct($entityManager);

    $this->storage = $this->entityManager->getStorage('commerce_store');
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\commerce_store\Entity\Store */
    $form = parent::form($form, $form_state);
    $entity = $this->entity;

    $defaultStore = $this->storage->loadDefault();
    $isDefault = TRUE;
    if ($defaultStore && $defaultStore->uuid() != $entity->uuid()) {
      $isDefault = FALSE;
    }
    $form['default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Default'),
      '#default_value' => $isDefault,
      '#disabled' => $isDefault || empty($defaultStore),
      '#weight' => 99,
    ];

    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    if ($form_state->getValue('default')) {
      $this->storage->markAsDefault($this->entity);
    }
    drupal_set_message($this->t('Saved the %label store.', [
      '%label' => $this->entity->label(),
    ]));
    $form_state->setRedirect('entity.commerce_store.collection');
  }

}
