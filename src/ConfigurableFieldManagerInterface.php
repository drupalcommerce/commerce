<?php

namespace Drupal\commerce;

use Drupal\entity\BundleFieldDefinition as EntityBundleFieldDefinition;

/**
 * Manages configurable fields based on field definitions.
 *
 * Allows for an easier way to create/delete configurable fields from code.
 */
interface ConfigurableFieldManagerInterface {

  /**
   * Creates a configurable field from the given field definition.
   *
   * @param \Drupal\entity\BundleFieldDefinition $field_definition
   *   The field definition.
   * @param bool $lock
   *   Whether the created field should be locked.
   *
   * @throws \InvalidArgumentException
   *   Thrown when given an incomplete field definition (missing name,
   *   target entity type ID, or target bundle).
   * @throws \RuntimeException
   *   Thrown when a field with the same name already exists.
   */
  public function createField(EntityBundleFieldDefinition $field_definition, $lock = TRUE);

  /**
   * Deletes the configurable field created from the given field definition.
   *
   * @param \Drupal\entity\BundleFieldDefinition $field_definition
   *   The field definition.
   *
   * @throws \InvalidArgumentException
   *   Thrown when given an incomplete field definition (missing name,
   *   target entity type ID, or target bundle).
   * @throws \RuntimeException
   *   Thrown when no matching field was found.
   */
  public function deleteField(EntityBundleFieldDefinition $field_definition);

  /**
   * Checks whether the configurable field has data.
   *
   * @param \Drupal\entity\BundleFieldDefinition $field_definition
   *   The field definition.
   *
   * @return bool
   *   TRUE if data was found, FALSE otherwise.
   *
   * @throws \InvalidArgumentException
   *   Thrown when given an incomplete field definition (missing name,
   *   target entity type ID, or target bundle).
   * @throws \RuntimeException
   *   Thrown when no matching field was found.
   */
  public function hasData(EntityBundleFieldDefinition $field_definition);

}
