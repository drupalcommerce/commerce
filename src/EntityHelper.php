<?php

namespace Drupal\commerce;

class EntityHelper {

  /**
   * Extracts the IDs of the given entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   The entities.
   *
   * @return array
   *   The entity IDs.
   */
  public static function extractIds(array $entities) {
    return array_map(function ($entity) {
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      return $entity->id();
    }, $entities);
  }

  /**
   * Extracts the labels of the given entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   The entities.
   *
   * @return array
   *   The entity labels.
   */
  public static function extractLabels(array $entities) {
    return array_map(function ($entity) {
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      return $entity->label();
    }, $entities);
  }

}
