<?php

namespace Drupal\commerce\Plugin\Commerce\Condition;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the interface for conditions that depend on the parent entity.
 *
 * For example, an order condition needing access to the parent promotion.
 *
 * @see \Drupal\commerce\Plugin\Commerce\Condition\ParentEntityAwareTrait
 */
interface ParentEntityAwareInterface {

  /**
   * Sets the parent entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $parent_entity
   *   The parent entity.
   *
   * @return $this
   */
  public function setParentEntity(EntityInterface $parent_entity);

}
