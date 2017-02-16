<?php

namespace Drupal\commerce\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Defines the interface for executable plugin field items.
 */
interface PluginItemInterface extends FieldItemInterface {

  /**
   * Gets the plugin instance.
   *
   * @param array $contexts
   *   An array of context values to pass to the plugin.
   *
   * @return \\Drupal\Core\Plugin\PluginBase
   *   The executable plugin.
   */
  public function getTargetInstance(array $contexts = []);

}
