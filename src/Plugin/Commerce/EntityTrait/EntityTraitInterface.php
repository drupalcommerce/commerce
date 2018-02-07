<?php

namespace Drupal\commerce\Plugin\Commerce\EntityTrait;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines the interface for entity traits.
 *
 * An entity trait represents a behavior that can be attached
 * to a specific entity bundle, by acting as a marker ("product is shippable")
 * and/or providing a set of fields.
 */
interface EntityTraitInterface extends PluginInspectionInterface {

  /**
   * Gets the entity trait label.
   *
   * @return string
   *   The entity trait label.
   */
  public function getLabel();

  /**
   * Gets the entity type IDs.
   *
   * These are the entity types that can have this trait.
   * If empty, defaults to all entity types.
   *
   * @return string[]
   *   The entity type IDs.
   */
  public function getEntityTypeIds();

  /**
   * Builds the field definitions.
   *
   * THe provided field definitions will be created as configurable
   * fields when the entity trait is installed for an entity type/bundle.
   *
   * @return \Drupal\entity\BundleFieldDefinition[]
   *   An array of field definitions, keyed by field name.
   */
  public function buildFieldDefinitions();

}
