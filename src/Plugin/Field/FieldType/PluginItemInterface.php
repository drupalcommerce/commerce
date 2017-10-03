<?php

namespace Drupal\commerce\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Defines the interface for executable plugin field items.
 */
interface PluginItemInterface extends FieldItemInterface {

  /**
   * Gets the plugin definition.
   *
   * @return array
   *   The plugin definition.
   */
  public function getTargetDefinition();

  /**
   * Gets the plugin instance.
   *
   * @param array $contexts
   *   An array of context values to pass to the plugin.
   *
   * @return \Drupal\Core\Plugin\PluginBase
   *   The plugin instance.
   */
  public function getTargetInstance(array $contexts = []);

}
