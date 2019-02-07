<?php

namespace Drupal\commerce_order;

use Drupal\commerce_order\Plugin\Commerce\AdjustmentType\AdjustmentType;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;

/**
 * Manages discovery and instantiation of commerce_adjustment_type plugins.
 *
 * @see \Drupal\commerce_order\Plugin\Commerce\AdjustmentType\AdjustmentTypeInterface
 * @see plugin_api
 */
class AdjustmentTypeManager extends DefaultPluginManager {

  /**
   * Default values for each plugin.
   *
   * @var array
   */
  protected $defaults = [
    'id' => '',
    'label' => '',
    'singular_label' => '',
    'plural_label' => '',
    'has_ui' => TRUE,
    'weight' => 0,
    'class' => AdjustmentType::class,
  ];

  /**
   * Constructs a new AdjustmentTypeManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend) {
    $this->moduleHandler = $module_handler;
    $this->setCacheBackend($cache_backend, 'commerce_adjustment_type', ['commerce_adjustment_type']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = new YamlDiscovery('commerce_adjustment_types', $this->moduleHandler->getModuleDirectories());
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
    foreach (['label'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new PluginException(sprintf('The adjustment type %s must define the %s property.', $plugin_id, $required_property));
      }
    }
    // Provide fallback labels for contrib adjustment types defined before 2.4.
    if (empty($definition['singular_label'])) {
      $label = mb_strtolower($definition['label']);
      $definition['singular_label'] = t('@label adjustment', ['@label' => $label]);
    }
    if (empty($definition['plural_label'])) {
      $label = mb_strtolower($definition['label']);
      $definition['plural_label'] = t('@label adjustments', ['@label' => $label]);
    }
  }

}
