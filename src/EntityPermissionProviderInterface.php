<?php

namespace Drupal\commerce;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Allows entity types to provide permissions.
 */
interface EntityPermissionProviderInterface {

  /**
   * Builds permissions for the given entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return array
   *   The permissions.
   */
  public function buildPermissions(EntityTypeInterface $entity_type);

}
