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
   * The provided field definitions will be created as configurable
   * fields when the entity trait is installed for an entity type/bundle.
   *
   * @return \Drupal\entity\BundleFieldDefinition[]
   *   An array of field definitions, keyed by field name.
   */
  public function buildFieldDefinitions();

  /**
   * Builds display mode settings for non-default modes.
   *
   * Display mode settings for default form and displays should be set
   * using BundleFieldDefinition::setDisplayOptions() and are processed by
   * ConfigurableFieldManager::createField(). To configure additional display
   * and form modes, return their configuration here. (Specifying default
   * settings here will overwrite the config from the field definition.)
   *
   * @return array
   *   The display mode configuration, keyed by field name, values described in
   *   ConfigurableFieldManagerInterface::configureFieldDisplayModes().
   */
  public function buildDisplayModes();

}
