<?php

namespace Drupal\commerce\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the referenceable plugin types event.
 *
 * @see \Drupal\commerce\Event\CommerceEvents
 */
class ReferenceablePluginTypesEvent extends Event {

  /**
   * The plugin types, in the id => label format.
   *
   * @var array
   */
  protected $pluginTypes;

  /**
   * Constructs a new ReferenceablePluginTypesEvent object.
   *
   * @param array $plugin_types
   *   The plugin types, in the id => label format.
   */
  public function __construct(array $plugin_types) {
    $this->pluginTypes = $plugin_types;
  }

  /**
   * Gets the plugin types.
   *
   * @return array
   *   The plugin types, in the id => label format.
   */
  public function getPluginTypes() {
    return $this->pluginTypes;
  }

  /**
   * Sets the plugin types.
   *
   * @param array $plugin_types
   *   The plugin types, in the id => label format.
   *
   * @return $this
   */
  public function setPluginTypes(array $plugin_types) {
    $this->pluginTypes = $plugin_types;
    return $this;
  }

}
