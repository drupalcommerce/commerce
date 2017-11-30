<?php

namespace Drupal\commerce;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

class CommerceContentEntityStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    $field_indexes = $entity_type->get('field_indexes');
    if (!empty($field_indexes)) {
      $tables = $this->getEntitySchemaTables();
      $schema_table = $tables['base_table'];
      // If the entity is translatable, there will be a data table for fields.
      // @see \Drupal\Core\Entity\Sql\SqlContentEntityStorage::initTableLayout
      if (isset($tables['data_table'])) {
        $schema_table = $tables['data_table'];
      }

      foreach ($field_indexes as $key => $value) {
        $schema[$schema_table]['indexes'][$entity_type->id() . '_field__' . $key] = (array) $value;
      }
    }
    return $schema;
  }

}
