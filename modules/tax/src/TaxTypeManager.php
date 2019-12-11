<?php

namespace Drupal\commerce_tax;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages tax type plugins.
 */
class TaxTypeManager extends DefaultPluginManager {

  /**
   * Constructs a new TaxTypeManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Commerce/TaxType', $namespaces, $module_handler, 'Drupal\commerce_tax\Plugin\Commerce\TaxType\TaxTypeInterface', 'Drupal\commerce_tax\Annotation\CommerceTaxType');

    $this->alterInfo('commerce_tax_type_info');
    $this->setCacheBackend($cache_backend, 'commerce_tax_type_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    foreach (['id', 'label'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new PluginException(sprintf('The tax type %s must define the %s property.', $plugin_id, $required_property));
      }
    }
  }

}
