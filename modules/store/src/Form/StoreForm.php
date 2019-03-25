<?php

namespace Drupal\commerce_store\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity\Form\EntityDuplicateFormTrait;

class StoreForm extends ContentEntityForm {

  use EntityDuplicateFormTrait;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\commerce_store\Entity\StoreInterface $store */
    $store = $this->entity;

    $form['path_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('URL path settings'),
      '#open' => !empty($form['path']['widget'][0]['alias']['#default_value']),
      '#group' => 'advanced',
      '#access' => !empty($form['path']['#access']) && $store->get('path')->access('edit'),
      '#attributes' => [
        'class' => ['path-form'],
      ],
      '#attached' => [
        'library' => ['path/drupal.path'],
      ],
      '#weight' => 91,
    ];
    $form['path']['#group'] = 'path_settings';

    /** @var \Drupal\commerce_store\StoreStorageInterface $store_storage */
    $store_storage = $this->entityTypeManager->getStorage('commerce_store');
    $default_store = $store_storage->loadDefault();
    $isDefault = TRUE;
    if ($default_store && $default_store->uuid() != $store->uuid()) {
      $isDefault = FALSE;
    }
    $form['default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Default'),
      '#default_value' => $isDefault,
      '#disabled' => $isDefault || empty($default_store),
      '#weight' => 98,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    $this->postSave($this->entity, $this->operation);
    if ($form_state->getValue('default')) {
      /** @var \Drupal\commerce_store\StoreStorageInterface $store_storage */
      $store_storage = $this->entityTypeManager->getStorage('commerce_store');
      $store_storage->markAsDefault($this->entity);
    }
    $this->messenger()->addMessage($this->t('Saved the %label store.', [
      '%label' => $this->entity->label(),
    ]));
    $form_state->setRedirect('entity.commerce_store.collection');
  }

}
