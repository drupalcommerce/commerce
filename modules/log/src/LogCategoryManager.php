<?php

namespace Drupal\commerce_log;

use Drupal\commerce_log\Plugin\LogCategory\LogCategory;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;

/**
 * Manages discovery and instantiation of commerce_log_category plugins.
 *
 * @see plugin_api
 */
class LogCategoryManager extends DefaultPluginManager implements LogCategoryManagerInterface {

  /**
   * Default values for each commerce_log_category plugin.
   *
   * @var array
   */
  protected $defaults = [
    'id' => '',
    'label' => '',
    'entity_type' => '',
    'class' => LogCategory::class,
  ];

  /**
   * Constructs a new LogCategoryManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend) {
    $this->moduleHandler = $module_handler;
    $this->setCacheBackend($cache_backend, 'commerce_log_category', ['commerce_log_category']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = new YamlDiscovery('commerce_log_categories', $this->moduleHandler->getModuleDirectories());
      $this->discovery->addTranslatableProperty('label', 'label_context');
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($this->discovery);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    $definition['id'] = $plugin_id;
    foreach (['label', 'entity_type'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new PluginException(sprintf('The commerce_log_category %s must define the %s property.', $plugin_id, $required_property));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitionsByEntityType($entity_type_id = NULL) {
    $definitions = $this->getDefinitions();
    if ($entity_type_id) {
      $definitions = array_filter($definitions, function ($definition) use ($entity_type_id) {
        return $definition['entity_type'] == $entity_type_id;
      });
    }

    return $definitions;
  }

}
