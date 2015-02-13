<?php
/**
 * @file
 * Contains TaxTypeResolverManager.
 */

namespace Drupal\commerce_tax;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * TaxTypeResolver plugin manager.
 */
class TaxTypeResolverManager extends DefaultPluginManager {

  /**
   * Constructs an TaxTypeResolverManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/CommerceTax/TaxTypeResolver', $namespaces, $module_handler, 'CommerceGuys\Tax\Resolver\TaxType\TaxTypeResolverInterface', 'Drupal\commerce_tax\Annotation\TaxTypeResolver');

    $this->alterInfo('tax_type_resolver_info');
    $this->setCacheBackend($cache_backend, 'tax_type_resolvers');
  }

}
