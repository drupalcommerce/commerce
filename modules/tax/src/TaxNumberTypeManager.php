<?php

namespace Drupal\commerce_tax;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages tax number type plugins.
 */
class TaxNumberTypeManager extends DefaultPluginManager implements TaxNumberTypeManagerInterface {

  /**
   * Default values for each tax number type plugin.
   *
   * @var array
   */
  protected $defaults = [
    'id' => '',
    'label' => '',
    'countries' => [],
  ];

  /**
   * Constructs a new TaxNumberTypeManager object.
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
    parent::__construct('Plugin/Commerce/TaxNumberType', $namespaces, $module_handler, 'Drupal\commerce_tax\Plugin\Commerce\TaxNumberType\TaxNumberTypeInterface', 'Drupal\commerce_tax\Annotation\CommerceTaxNumberType');

    $this->alterInfo('commerce_tax_number_type_info');
    $this->setCacheBackend($cache_backend, 'commerce_tax_number_type_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    foreach (['id', 'label'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new PluginException(sprintf('The tax number type %s must define the %s property.', $plugin_id, $required_property));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId($country_code) {
    $definitions = $this->getDefinitions();
    foreach ($definitions as $plugin_id => $definition) {
      if (in_array($country_code, $definition['countries'])) {
        return $plugin_id;
      }
    }
    return 'other';
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'other';
  }

}
