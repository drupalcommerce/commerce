<?php

namespace Drupal\commerce_store\Form;

use Drupal\commerce\Form\CommerceBundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\Form\EntityDuplicateFormTrait;
use Drupal\language\Entity\ContentLanguageSettings;

class StoreTypeForm extends CommerceBundleEntityFormBase {

  use EntityDuplicateFormTrait;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\commerce_store\Entity\StoreTypeInterface $store_type */
    $store_type = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $store_type->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $store_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_store\Entity\StoreType::load',
      ],
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => !$store_type->isNew(),

    ];
    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => $this->t('This text will be displayed on the <em>Add store</em> page.'),
      '#default_value' => $store_type->getDescription(),
    ];
    $form = $this->buildTraitForm($form, $form_state);

    if ($this->moduleHandler->moduleExists('language')) {
      $form['language'] = [
        '#type' => 'details',
        '#title' => $this->t('Language settings'),
        '#group' => 'additional_settings',
      ];
      $form['language']['language_configuration'] = [
        '#type' => 'language_configuration',
        '#entity_information' => [
          'entity_type' => 'commerce_store',
          'bundle' => $store_type->id(),
        ],
        '#default_value' => ContentLanguageSettings::loadByEntityTypeBundle('commerce_store', $store_type->id()),
      ];
      $form['#submit'][] = 'language_configuration_element_submit';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->validateTraitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    $this->postSave($this->entity, $this->operation);
    $this->submitTraitForm($form, $form_state);

    $this->messenger()->addMessage($this->t('Saved the %label store type.', [
      '%label' => $this->entity->label(),
    ]));
    $form_state->setRedirect('entity.commerce_store_type.collection');
  }

}
