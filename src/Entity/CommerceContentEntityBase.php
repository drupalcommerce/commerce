<?php

namespace Drupal\commerce\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Language\LanguageInterface;

/**
 * Provides the base class for Commerce content entities.
 */
class CommerceContentEntityBase extends ContentEntityBase implements CommerceContentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public function getTranslatedReferencedEntities($field_name) {
    $referenced_entities = $this->get($field_name)->referencedEntities();
    return $this->ensureTranslations($referenced_entities);
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatedReferencedEntity($field_name) {
    $referenced_entities = $this->getTranslatedReferencedEntities($field_name);
    return reset($referenced_entities);
  }

  /**
   * Ensures entities are in the current entity's language, if possible.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $entities
   *   The entities to process.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   The processed entities.
   */
  protected function ensureTranslations(array $entities) {
    if ($this->isTranslatable()) {
      $langcode = $this->language()->getId();
    }
    else {
      $langcode = $this->languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    }
    foreach ($entities as $index => $entity) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entities[$index] = ($entity->hasTranslation($langcode)) ? $entity->getTranslation($langcode) : $entity;
    }

    return $entities;
  }

}
