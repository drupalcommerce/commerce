<?php

namespace Drupal\commerce;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * A collection that stores a single plugin, aware of its parent entity ID.
 */
class CommerceSinglePluginCollection extends DefaultSingleLazyPluginCollection {

  /**
   * The entity ID this plugin collection belongs to.
   *
   * @var string
   */
  protected $entityId;

  /**
   * Constructs a new CommerceSinglePluginCollection object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param string $instance_id
   *   The ID of the plugin instance.
   * @param array $configuration
   *   An array of configuration.
   * @param string $entity_id
   *   The entity ID this plugin collection belongs to.
   */
  public function __construct(PluginManagerInterface $manager, $instance_id, array $configuration, $entity_id) {
    $this->entityId = $entity_id;
    // The parent constructor initializes the plugin, so it needs to be called
    // after $this->entityId is set.
    parent::__construct($manager, $instance_id, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    if (!$instance_id) {
      throw new PluginException("The entity '{$this->entityId}' did not specify a plugin.");
    }

    $configuration = ['_entity_id' => $this->entityId] + $this->configuration;
    $plugin = $this->manager->createInstance($instance_id, $configuration);
    $this->set($instance_id, $plugin);
  }

}
