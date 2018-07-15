<?php

namespace Drupal\commerce;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class EntityUuidMapper implements EntityUuidMapperInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * UUID to ID map, grouped by entity type ID.
   *
   * @var array
   */
  protected $map = [];

  /**
   * Constructs a new EntityUuidMapper object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entity_type_manager) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function mapToIds($entity_type_id, array $uuids) {
    // Fetch known UUIDs from the static cache.
    $ids = [];
    foreach ($uuids as $index => $uuid) {
      if (isset($this->map[$entity_type_id][$uuid])) {
        $ids[$uuid] = $this->map[$entity_type_id][$uuid];
        unset($uuids[$index]);
      }
    }

    // Map the remaining UUIDs from the database.
    if (!empty($uuids)) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      $id_key = $entity_type->getKey('id');
      $uuid_key = $entity_type->getKey('uuid');
      // Query the storage directly to avoid the performance impact of loading
      // the full entities.
      $loaded_uuids = $this->database->select($entity_type->getBaseTable(), 't')
        ->fields('t', [$uuid_key, $id_key])
        ->condition($uuid_key, $uuids, 'IN')
        ->execute()
        ->fetchAllKeyed(1, 0);

      foreach ($loaded_uuids as $id => $uuid) {
        $ids[$uuid] = $id;
        $this->map[$entity_type_id][$uuid] = $id;
      }
    }

    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function mapFromIds($entity_type_id, array $ids) {
    // Fetch known IDs from the static cache.
    $uuids = [];
    foreach ($ids as $index => $id) {
      if (isset($this->map[$entity_type_id])) {
        $uuid = array_search($id, $this->map[$entity_type_id]);
        if ($uuid) {
          $uuids[$id] = $uuid;
          unset($ids[$index]);
        }
      }
    }

    // Map the remaining IDs from the database.
    if (!empty($ids)) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      $id_key = $entity_type->getKey('id');
      $uuid_key = $entity_type->getKey('uuid');
      // Query the storage directly to avoid the performance impact of loading
      // the full entities.
      $loaded_ids = $this->database->select($entity_type->getBaseTable(), 't')
        ->fields('t', [$uuid_key, $id_key])
        ->condition($id_key, $ids, 'IN')
        ->execute()
        ->fetchAllKeyed(0, 1);

      foreach ($loaded_ids as $uuid => $id) {
        $uuids[$id] = $uuid;
        $this->map[$entity_type_id][$uuid] = $id;
      }
    }

    return $uuids;
  }

}
