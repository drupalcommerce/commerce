<?php

namespace Drupal\commerce\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides the interface for Commerce content entities.
 */
interface CommerceContentEntityInterface extends ContentEntityInterface {

  /**
   * Gets the translations of an entity reference field.
   *
   * @param string $field_name
   *   The entity reference field name.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   The translated entities.
   */
  public function getTranslatedReferencedEntities($field_name);

  /**
   * Gets the translation of a referenced entity.
   *
   * @param string $field_name
   *   The entity reference field name.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The translated entity, or NULL if not found.
   */
  public function getTranslatedReferencedEntity($field_name);

}
