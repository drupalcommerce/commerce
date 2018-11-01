<?php

namespace Drupal\commerce\Plugin\Commerce\Condition;

use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a trait for implementing ParentEntityAwareInterface.
 */
trait ParentEntityAwareTrait {

  /**
   * The parent entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $parentEntity;

  /**
   * {@inheritdoc}
   */
  public function setParentEntity(EntityInterface $parent_entity) {
    $this->parentEntity = $parent_entity;
    return $this;
  }

}
