<?php

namespace Drupal\commerce_bundle_plugin_test;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages discovery and instantiation of BundlePluginTest plugins.
 *
 * @see \Drupal\commerce_bundle_plugin_test\Annotation\BundlePluginTest
 * @see plugin_api
 */
class BundlePluginTestManager extends DefaultPluginManager {

  /**
   * Constructs a new BundlePluginTestManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/BundlePluginTest', $namespaces, $module_handler, 'Drupal\commerce_bundle_plugin_test\Plugin\BundlePluginTest\BundlePluginTestInterface', 'Drupal\commerce_bundle_plugin_test\Annotation\BundlePluginTest');

    $this->alterInfo('bundle_plugin_test_info');
    $this->setCacheBackend($cache_backend, 'bundle_plugin_test_plugins');
  }

}
