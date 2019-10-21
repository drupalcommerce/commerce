<?php

namespace Drupal\commerce;

/**
 * Provides getters for former entity manager services.
 *
 * This allows classes to work with Drupal 8.7 and 8.8+ simultaneously.
 */
trait EntityManagerBridgeTrait {

  /**
   * Gets the EntityTypeManager service.
   */
  protected function getEntityTypeManager() {
    if (!isset($this->entityTypeManager)) {
      return \Drupal::entityTypeManager();
    }
    return $this->entityTypeManager;
  }

}
