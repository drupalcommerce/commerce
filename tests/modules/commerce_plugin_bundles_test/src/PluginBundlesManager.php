<?php

namespace Drupal\commerce_plugin_bundles_test;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;

/**
 * Managet for test plugin bundles plugin.
 */
class PluginBundlesManager extends DefaultPluginManager {

  protected $defaults = [
    'label' => '',
    'class' => 'Drupal\commerce_plugin_bundles_test\PluginBundle',
  ];

  /**
   * Constructs a new PluginBundlesManager object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
    $this->setCacheBackend($cache_backend, 'plugin_bundles');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = new YamlDiscovery('plugin_bundles', $this->moduleHandler->getModuleDirectories());
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($this->discovery);
    }
    return $this->discovery;
  }

}
