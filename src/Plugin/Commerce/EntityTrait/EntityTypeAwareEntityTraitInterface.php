<?php

namespace Drupal\commerce\Plugin\Commerce\EntityTrait;

/**
 * Defines the interface for entity traits that may vary by entity type.
 *
 * The entity trait may support multiple entity types, yet the exact behavior
 * of the trait is dependent on the entity type. This interface is provided as
 * an extension of the base interface so as not to break backward-compatibility.
 */
interface EntityTypeAwareEntityTraitInterface extends EntityTraitInterface {

  /**
   * Builds the field definitions.
   *
   * The provided field definitions will be created as configurable
   * fields when the entity trait is installed for an entity type/bundle.
   *
   * @param $entity_type_id string The entity type ID.
   *
   * @return \Drupal\commerce\BundleFieldDefinition[]
   *   An array of field definitions, keyed by field name.
   */
  public function buildFieldDefinitions($entity_type_id);

}
