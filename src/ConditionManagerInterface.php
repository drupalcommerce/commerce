<?php

namespace Drupal\commerce;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;

/**
 * Defines the interface for commerce_condition plugin managers.
 */
interface ConditionManagerInterface extends CategorizingPluginManagerInterface {

  /**
   * Gets the plugin definitions for the given entity types.
   *
   * @param array $entity_types
   *   The entity type IDs.
   *
   * @return array
   *   The plugin definitions.
   */
  public function getDefinitionsByEntityTypes(array $entity_types);

}
