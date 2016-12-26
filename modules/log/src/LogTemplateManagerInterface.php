<?php

namespace Drupal\commerce_log;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Defines the interface for commerce_log_template plugin managers.
 */
interface LogTemplateManagerInterface extends PluginManagerInterface {

  /**
   * Gets the log template labels grouped by category.
   *
   * @param string $entity_type_id
   *   (optional) The entity type ID to filter by. If provided, only
   *   log templates that belong to categories with the specified entity
   *   type will be returned.
   *
   * @return array
   *   Keys are category labels, and values are arrays of which the keys are
   *   log template IDs and the values are log template labels.
   */
  public function getLabelsByCategory($entity_type_id = NULL);

}
