<?php

namespace Drupal\commerce_log;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Defines the interface for commerce_log_template plugin managers.
 */
interface LogTemplateManagerInterface extends PluginManagerInterface {

  /**
   * Gets the grouped commerce_log_template labels.
   *
   * @param string $entity_type_id
   *   (optional) The entity type id to filter by. If provided, only
   *   commerce_log_template that belong to groups with the specified entity
   *   type will be returned.
   *
   * @return array
   *   Keys are category labels, and values are arrays of which the keys are
   *   commerce_log_template IDs and the values are commerce_log_template labels.
   */
  public function getGroupedLabels($entity_type_id = NULL);

}
