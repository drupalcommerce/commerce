<?php

/**
 * @file
 * Contains \Drupal\commerce\PluginCallbackTrait.
 */

namespace Drupal\commerce;

trait PluginCallbackTrait {

  /**
   * Instantiates the current plugin class and calls a method on it.
   */
  public static function __callStatic($name, array $arguments) {
    if (preg_match('/^instantiate#(.+?)#(.+?)$/', $name)) {
      list(, $pluginType, $pluginId, $method) = explode('#', $name);
      /** @var \Drupal\Component\Plugin\PluginManagerInterface $manager */
      $manager = \Drupal::service('plugin.manager.' . $pluginType);
      $plugin = $manager->createInstance($pluginId);

      return call_user_func_array([$plugin, $method], $arguments);
    }
  }

}
