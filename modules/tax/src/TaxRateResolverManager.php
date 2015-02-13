<?php
/**
 * @file
 * Contains TaxRateResolverManager.
 */

namespace Drupal\commerce_tax;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * TaxRateResolver plugin manager.
 */
class TaxRateResolverManager extends DefaultPluginManager {

  /**
   * Constructs an TaxRateResolverManager object.
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
    parent::__construct('Plugin/CommerceTax/TaxRateResolver', $namespaces, $module_handler, 'CommerceGuys\Tax\Resolver\TaxRate\TaxRateResolverInterface', 'Drupal\commerce_tax\Annotation\TaxRateResolver');

    $this->alterInfo('tax_rate_resolver_info');
    $this->setCacheBackend($cache_backend, 'tax_rate_resolvers');
  }

}
