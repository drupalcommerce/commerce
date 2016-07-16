<?php

namespace Drupal\commerce;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BundlePluginHandler implements BundlePluginHandlerInterface {

  /**
   * The entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The bundle plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * Constructs a new BundlePluginHandler object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   The bundle plugin manager.
   */
  public function __construct(EntityTypeInterface $entity_type, PluginManagerInterface $plugin_manager) {
    $this->entityType = $entity_type;
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('plugin.manager.' . $entity_type->get('bundle_plugin_type'))
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleInfo() {
    $bundles = [];
    foreach ($this->pluginManager->getDefinitions() as $plugin_id => $definition) {
      $bundles[$plugin_id] = [
        'label' => $definition['label'],
        'translatable' => $this->entityType->isTranslatable(),
        'provider' => $definition['provider'],
      ];
    }
    return $bundles;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldStorageDefinitions() {
    $definitions = [];
    foreach (array_keys($this->pluginManager->getDefinitions()) as $plugin_id) {
      /** @var \Drupal\commerce\BundlePluginInterface $plugin */
      $plugin = $this->pluginManager->createInstance($plugin_id);
      $definitions += $plugin->buildFieldDefinitions();
    }
    // Ensure the presence of required keys which aren't set by the plugin.
    foreach ($definitions as $field_name => $definition) {
      $definition->setName($field_name);
      $definition->setTargetEntityTypeId($this->entityType->id());
      $definitions[$field_name] = $definition;
    }

    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinitions($bundle) {
    /** @var \Drupal\commerce\BundlePluginInterface $plugin */
    $plugin = $this->pluginManager->createInstance($bundle);
    $definitions = $plugin->buildFieldDefinitions();
    // Ensure the presence of required keys which aren't set by the plugin.
    foreach ($definitions as $field_name => $definition) {
      $definition->setName($field_name);
      $definition->setTargetEntityTypeId($this->entityType->id());
      $definition->setTargetBundle($bundle);
      $definitions[$field_name] = $definition;
    }

    return $definitions;
  }

}
