<?php

namespace Drupal\commerce\Plugin\Commerce\InlineForm;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Provides an inline form for managing a content entity.
 *
 * If the content entity is translatable, and the inline form is used as a part
 * of another content entity form, the entity langcodes will be kept in sync.
 * Translating the parent entity will also translate the entity managed by the
 * inline form.
 *
 * @CommerceInlineForm(
 *   id = "content_entity",
 *   label = @Translation("Content entity"),
 * )
 */
class ContentEntity extends EntityInlineFormBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'form_mode' => 'default',
      'skip_save' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function requiredConfiguration() {
    return ['form_mode'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildInlineForm(array $inline_form, FormStateInterface $form_state) {
    $inline_form = parent::buildInlineForm($inline_form, $form_state);

    assert($this->entity instanceof ContentEntityInterface);
    if ($this->entity->isTranslatable()) {
      $this->entity = $this->ensureTranslation($this->entity, $form_state);
    }
    $form_display = EntityFormDisplay::collectRenderDisplay($this->entity, $this->configuration['form_mode']);
    $form_display->buildForm($this->entity, $inline_form, $form_state);

    return $inline_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateInlineForm(array &$inline_form, FormStateInterface $form_state) {
    parent::validateInlineForm($inline_form, $form_state);

    assert($this->entity instanceof ContentEntityInterface);
    $form_display = EntityFormDisplay::collectRenderDisplay($this->entity, 'default');
    $form_display->extractFormValues($this->entity, $inline_form, $form_state);
    $form_display->validateFormValues($this->entity, $inline_form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitInlineForm(array &$inline_form, FormStateInterface $form_state) {
    parent::submitInlineForm($inline_form, $form_state);

    assert($this->entity instanceof ContentEntityInterface);
    $form_display = EntityFormDisplay::collectRenderDisplay($this->entity, 'default');
    $form_display->extractFormValues($this->entity, $inline_form, $form_state);
    if ($this->entity->isTranslatable()) {
      $this->updateLangcode($this->entity, $inline_form, $form_state);
    }
    if (empty($this->configuration['skip_save'])) {
      $this->entity->save();
    }
  }

  /**
   * Updates the entity langcode to match the form langcode.
   *
   * Allows the user to select a different language through the langcode
   * form element, which is then transferred to form state.
   *
   * Performed only if the inline form doesn't have a langcode form element
   * of its own.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param array $inline_form
   *   The inline form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function updateLangcode(ContentEntityInterface $entity, array $inline_form, FormStateInterface $form_state) {
    $form_langcode = $form_state->get('langcode');
    if (empty($form_langcode)) {
      // The top-level form is not a content entity form.
      return;
    }
    $langcode_key = $this->getLangcodeKey($entity);
    // The inline form has a visible langcode element, don't override its value.
    if (isset($inline_form[$langcode_key]) && Element::isVisibleElement($inline_form[$langcode_key])) {
      return;
    }

    $entity_langcode = $entity->get($langcode_key)->value;
    if ($entity_langcode != $form_langcode && !$entity->hasTranslation($form_langcode)) {
      $entity->set($langcode_key, $form_langcode);
    }
  }

  /**
   * Ensures the correct entity translation.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The translated entity.
   *
   * @see \Drupal\Core\Entity\ContentEntityForm::initFormLangcodes()
   */
  protected function ensureTranslation(ContentEntityInterface $entity, FormStateInterface $form_state) {
    $form_langcode = $form_state->get('langcode');
    if (empty($form_langcode)) {
      // The top-level form is not a content entity form.
      return $entity;
    }

    $langcode_key = $this->getLangcodeKey($entity);
    $entity_langcode = $entity->get($langcode_key)->value;
    if ($this->isTranslating($form_state) && !$entity->hasTranslation($form_langcode)) {
      // Create a translation from the source language values.
      $source = $form_state->get(['content_translation', 'source']);
      $source_langcode = $source ? $source->getId() : $entity_langcode;
      if (!$entity->hasTranslation($source_langcode)) {
        $entity->addTranslation($source_langcode, $entity->toArray());
      }
      $source_translation = $entity->getTranslation($source_langcode);
      $entity->addTranslation($form_langcode, $source_translation->toArray());
      $translation = $entity->getTranslation($form_langcode);
      $translation->set('content_translation_source', $source_langcode);
      // Make sure we do not inherit the affected status from the source values.
      if ($entity->getEntityType()->isRevisionable()) {
        $translation->setRevisionTranslationAffected(NULL);
      }
    }

    if ($entity_langcode != $form_langcode && $entity->hasTranslation($form_langcode)) {
      // Switch to the needed translation.
      $entity = $entity->getTranslation($form_langcode);
    }

    return $entity;
  }

  /**
   * Determines whether there's a translation in progress.
   *
   * If the parent entity is being translated, then all inline entities
   * are candidates for translating as well.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   TRUE if translating is in progress, FALSE otherwise.
   *
   * @see \Drupal\Core\Entity\ContentEntityForm::initFormLangcodes()
   */
  protected function isTranslating(FormStateInterface $form_state) {
    $form_langcode = $form_state->get('langcode');
    $default_langcode = $form_state->get('entity_default_langcode');

    return $form_langcode && $form_langcode != $default_langcode;
  }

  /**
   * Gets the langcode key for the given entity.
   *
   * Ensures that the key is not empty, which is allowed by Drupal
   * even if the entity itself is translatable.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return string
   *   The langcode key.
   *
   * @throws \RuntimeException
   *   Thrown when the entity type does not have a langcode key defined.
   */
  protected function getLangcodeKey(ContentEntityInterface $entity) {
    $entity_type = $entity->getEntityType();
    $langcode_key = $entity_type->getKey('langcode');
    if (empty($langcode_key)) {
      throw new \RuntimeException(sprintf('The entity type %s did not specify a langcode key.', $entity_type->id()));
    }

    return $langcode_key;
  }

}
