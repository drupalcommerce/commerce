<?php

/**
 * @file
 * Contains \Drupal\commerce\Form\StoreForm.
 */

namespace Drupal\commerce_store\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($entityTypeManager);

    $this->storage = $entityTypeManager->getStorage('commerce_store');
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\commerce_store\Entity\Store */
    $form = parent::form($form, $form_state);
    $entity = $this->entity;

    $default_store = $this->storage->loadDefault();
    $isDefault = TRUE;
    if ($default_store && $default_store->uuid() != $entity->uuid()) {
      $isDefault = FALSE;
    }
    $form['default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Default'),
      '#default_value' => $isDefault,
      '#disabled' => $isDefault || empty($default_store),
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
