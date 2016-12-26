<?php

namespace Drupal\commerce_log;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Defines the interface for commerce_log_category plugin managers.
 */
interface LogCategoryManagerInterface extends PluginManagerInterface {

  /**
   * Gets the definitions filtered by entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return array
   *   The definitions.
   */
  public function getDefinitionsByEntityType($entity_type_id = NULL);

}
