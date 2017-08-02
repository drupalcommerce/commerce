<?php

namespace Drupal\commerce_tax;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * A collection of tax type plugins.
 */
class TaxTypePluginCollection extends DefaultSingleLazyPluginCollection {

  /**
   * The tax type entity ID this plugin collection belongs to.
   *
   * @var string
   */
  protected $entityId;

  /**
   * Constructs a new TaxTypePluginCollection object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param string $instance_id
   *   The ID of the plugin instance.
   * @param array $configuration
   *   An array of configuration.
   * @param string $entity_id
   *   The tax type entity ID this plugin collection belongs to.
   */
  public function __construct(PluginManagerInterface $manager, $instance_id, array $configuration, $entity_id) {
    parent::__construct($manager, $instance_id, $configuration);

    $this->entityId = $entity_id;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    if (!$instance_id) {
      throw new PluginException("The tax type '{$this->entityId}' did not specify a plugin.");
    }

    $configuration = $this->configuration + ['_entity_id' => $this->entityId];
    $plugin = $this->manager->createInstance($instance_id, $configuration);
    $this->set($instance_id, $plugin);
  }

}
