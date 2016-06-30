<?php

namespace Drupal\commerce\PluginForm;

/**
 * Provides an interface for plugin form factories.
 */
interface PluginFormFactoryInterface {
  
  /**
   * Creates an instance of a plugin form for the given operation.
   *
   * @param \Drupal\commerce\PluginForm\PluginWithFormsInterface $plugin
   *   The plugin. 
   * @param string $operation
   *   The name of the operation.
   *
   * @return \Drupal\Core\Plugin\PluginFormInterface
   *   The plugin form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if no form class is defined for the given operation, or the
   *   defined form class does not exist.
   */
  public function createInstance(PluginWithFormsInterface $plugin, $operation);

}