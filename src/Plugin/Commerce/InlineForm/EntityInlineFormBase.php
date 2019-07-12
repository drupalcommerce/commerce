<?php

namespace Drupal\commerce\Plugin\Commerce\InlineForm;

use Drupal\Core\Entity\EntityInterface;

/**
 * Provides the base class for inline forms that operate on an entity.
 */
abstract class EntityInlineFormBase extends InlineFormBase implements EntityInlineFormInterface {

  /**
   * The entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntity(EntityInterface $entity) {
    // Modifying $this->>entity must not modify the entity in the storage
    // static cache, since that can persist between form builds.
    $this->entity = clone $entity;
    return $this;
  }

}
