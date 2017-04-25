<?php

namespace Drupal\commerce\Form;

use Drupal\commerce\EntityTraitManagerInterface;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CommerceBundleEntityFormBase extends BundleEntityFormBase {

  /**
   * The entity trait manager.
   *
   * @var \Drupal\commerce\EntityTraitManagerInterface
   */
  protected $traitManager;

  /**
   * Constructs a new CommerceBundleEntityFormBase object.
   *
   * @param \Drupal\commerce\EntityTraitManagerInterface $trait_manager
   *   The entity trait manager.
   */
  public function __construct(EntityTraitManagerInterface $trait_manager) {
    $this->traitManager = $trait_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.commerce_entity_trait')
    );
  }

  /**
   * Builds the trait form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The parent form with the trait form elements added.
   */
  protected function buildTraitForm(array $form, FormStateInterface $form_state) {
    $target_entity_type_id = $this->entity->getEntityType()->getBundleOf();
    /** @var \Drupal\commerce\Entity\CommerceBundleEntityInterface $entity */
    $entity = $this->entity;

    $used_traits = $entity->getTraits();
    $traits = $this->traitManager->getDefinitionsByEntityType($target_entity_type_id);
    $trait_options = array_map(function ($trait) {
      return $trait['label'];
    }, $traits);
    asort($trait_options);

    $form['original_traits'] = [
      '#type' => 'value',
      '#value' => $used_traits,
    ];
    $form['traits'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Traits'),
      '#options' => $trait_options,
      '#default_value' => $used_traits,
      '#access' => count($traits) > 0,
    ];
    // Disable options which cannot be unset because of existing data.
    $disabled_traits = [];
    if (!$entity->isNew()) {
      foreach ($used_traits as $trait_id) {
        $trait = $this->traitManager->createInstance($trait_id);
        if (!$this->traitManager->canUninstallTrait($trait, $target_entity_type_id, $this->entity->id())) {
          $form['traits'][$trait_id] = [
            '#disabled' => TRUE,
          ];
          $disabled_traits[] = $trait_id;
        }
      }
    }
    $form['disabled_traits'] = [
      '#type' => 'value',
      '#value' => $disabled_traits,
    ];

    return $form;
  }

  /**
   * Validates the trait form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function validateTraitForm(array &$form, FormStateInterface $form_state) {
    $traits = array_filter($form_state->getValue('traits'));
    $disabled_traits = $form_state->getValue('disabled_traits');
    $traits = array_unique(array_merge($disabled_traits, $traits));
    $original_traits = $form_state->getValue('original_traits');
    $installed_traits = [];
    foreach ($original_traits as $trait_id) {
      $installed_traits[$trait_id] = $this->traitManager->createInstance($trait_id);
    }
    $selected_traits = array_diff($traits, $original_traits);
    foreach ($selected_traits as $trait_id) {
      $trait = $this->traitManager->createInstance($trait_id);
      $conflicts = $this->traitManager->detectConflicts($trait, $installed_traits);
      if ($conflicts) {
        $conflicts = array_map(function ($trait) {
          return $trait->getLabel();
        }, $conflicts);

        $form_state->setError($form['traits'], $this->t('The @trait trait is in conflict with the following traits: @conflict.', [
          '@trait' => $trait->getLabel(),
          '@conflict' => implode(', ', $conflicts),
        ]));
        break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $entity = parent::buildEntity($form, $form_state);
    $disabled_traits = $form_state->getValue('disabled_traits');
    /** @var \Drupal\commerce\Entity\CommerceBundleEntityInterface $entity */
    $traits = $entity->getTraits();
    $traits = array_filter($traits);
    $traits = array_values($traits);
    $traits = array_unique(array_merge($disabled_traits, $traits));
    $entity->setTraits($traits);

    return $entity;
  }

  /**
   * Submits the trait form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function submitTraitForm(array $form, FormStateInterface $form_state) {
    $target_entity_type_id = $this->entity->getEntityType()->getBundleOf();
    /** @var \Drupal\commerce\Entity\CommerceBundleEntityInterface $entity */
    $entity = $this->entity;
    $traits = $entity->getTraits();
    $original_traits = $form_state->getValue('original_traits');
    $selected_traits = array_diff($traits, $original_traits);
    $unselected_traits = array_diff($original_traits, $traits);
    foreach ($selected_traits as $trait_id) {
      $trait = $this->traitManager->createInstance($trait_id);
      $this->traitManager->installTrait($trait, $target_entity_type_id, $this->entity->id());
    }
    foreach ($unselected_traits as $trait_id) {
      $trait = $this->traitManager->createInstance($trait_id);
      $this->traitManager->uninstallTrait($trait, $target_entity_type_id, $this->entity->id());
    }
  }

}
