<?php

namespace Drupal\commerce;

/**
 * Provides getters for former entity manager services.
 *
 * This allows classes to work with Drupal 8.7 and 8.8+ simultaneously.
 */
trait EntityManagerBridgeTrait {

  /**
   * Gets the entity field manager.
   *
   * @return \Drupal\Core\Entity\EntityFieldManagerInterface
   *   The entity field manager.
   */
  protected function getEntityFieldManager() {
    if (!isset($this->entityFieldManager)) {
      return \Drupal::service('entity_field.manager');
    }
    return $this->entityFieldManager;
  }

  /**
   * Gets the entity type bundle info.
   *
   * @return \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   *   The entity type bundle info.
   */
  protected function getEntityTypeBundleInfo() {
    if (!isset($this->entityTypeBundleInfo)) {
      return \Drupal::service('entity_type.bundle.info');
    }
    return $this->entityTypeBundleInfo;
  }

  /**
   * Gets the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  protected function getEntityTypeManager() {
    if (!isset($this->entityTypeManager)) {
      return \Drupal::entityTypeManager();
    }
    return $this->entityTypeManager;
  }

}
