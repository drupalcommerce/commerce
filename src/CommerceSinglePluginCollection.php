<?php

namespace Drupal\commerce;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * A collection that stores a single plugin, aware of its parent entity.
 *
 * For backwards compatibility reasons the collection supports passing
 * either the entity or the entity ID. Passing the full entity is preferred.
 */
class CommerceSinglePluginCollection extends DefaultSingleLazyPluginCollection {

  /**
   * The parent entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface|string
   */
  protected $parentEntity;

  /**
   * Constructs a new CommerceSinglePluginCollection object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param string $instance_id
   *   The ID of the plugin instance.
   * @param array $configuration
   *   An array of configuration.
   * @param \Drupal\Core\Entity\EntityInterface|string $parent_entity
   *   The parent entity.
   */
  public function __construct(PluginManagerInterface $manager, $instance_id, array $configuration, $parent_entity) {
    $this->parentEntity = $parent_entity;
    // The parent constructor initializes the plugin, so it needs to be called
    // after $this->parentEntity is set.
    parent::__construct($manager, $instance_id, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    if (!$instance_id) {
      throw new PluginException('The parent entity did not specify a plugin.');
    }

    if ($this->parentEntity instanceof EntityInterface) {
      $configuration = ['_entity' => $this->parentEntity];
    }
    else {
      $configuration = ['_entity_id' => $this->parentEntity];
    }
    $configuration += $this->configuration;
    $plugin = $this->manager->createInstance($instance_id, $configuration);
    $this->set($instance_id, $plugin);
  }

}
