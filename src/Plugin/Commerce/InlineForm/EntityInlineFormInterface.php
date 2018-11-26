<?php

namespace Drupal\commerce\Plugin\Commerce\InlineForm;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the interface for inline forms that operate on an entity.
 *
 * @see \Drupal\commerce\Plugin\Commerce\InlineForm\InlineFormInterface
 */
interface EntityInlineFormInterface extends InlineFormInterface {

  /**
   * Gets the entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity.
   */
  public function getEntity();

  /**
   * Sets the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return $this
   */
  public function setEntity(EntityInterface $entity);

}
