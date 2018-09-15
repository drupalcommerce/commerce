<?php

namespace Drupal\commerce;

/**
 * Maps entity UUIDs to entity IDs, and vice-versa.
 */
interface EntityUuidMapperInterface {

  /**
   * Maps the given entity UUIDs to entity IDs.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param array $uuids
   *   THe entity UUIDs.
   *
   * @return array
   *   The entity IDs.
   */
  public function mapToIds($entity_type_id, array $uuids);

  /**
   * Maps the given entity IDs to entity UUIDs.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param array $ids
   *   THe entity IDs.
   *
   * @return array
   *   The entity UUIDs.
   */
  public function mapFromIds($entity_type_id, array $ids);

}
