<?php

namespace Drupal\commerce_number_pattern;

use Drupal\commerce_number_pattern\Annotation\CommerceNumberPattern;
use Drupal\commerce_number_pattern\Plugin\Commerce\NumberPattern\NumberPatternInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages discovery and instantiation of number pattern plugins.
 *
 * @see \Drupal\commerce_number_pattern\Annotation\CommerceNumberPattern
 * @see plugin_api
 */
class NumberPatternManager extends DefaultPluginManager {

  /**
   * Constructs a new NumberPatternManager object.
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
    parent::__construct(
      'Plugin/Commerce/NumberPattern', $namespaces, $module_handler, NumberPatternInterface::class, CommerceNumberPattern::class
    );

    $this->alterInfo('commerce_number_pattern_info');
    $this->setCacheBackend($cache_backend, 'commerce_number_pattern_plugins');
  }

}
