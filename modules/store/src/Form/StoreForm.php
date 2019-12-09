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

    if (isset($form['is_default'])) {
      $form['is_default']['#group'] = 'footer';
      $form['is_default']['#disabled'] = $store->isDefault();
      if (!$store->isDefault()) {
        /** @var \Drupal\commerce_store\StoreStorageInterface $store_storage */
        $store_storage = $this->entityTypeManager->getStorage('commerce_store');
        $default_store = $store_storage->loadDefault();
        if (!$default_store || $default_store->id() == $store->id()) {
          $form['is_default']['widget']['value']['#default_value'] = TRUE;
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    $this->postSave($this->entity, $this->operation);
    $this->messenger()->addMessage($this->t('Saved the %label store.', [
      '%label' => $this->entity->label(),
    ]));
    $form_state->setRedirect('entity.commerce_store.collection');
  }

}
