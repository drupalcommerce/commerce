<?php

namespace Drupal\commerce_checkout;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages checkout flow plugins.
 */
class CheckoutFlowManager extends DefaultPluginManager {

  /**
   * Default values for each checkout flow plugin.
   *
   * @var array
   */
  protected $defaults = [
    'id' => '',
    'label' => '',
  ];

  /**
   * Constructs a new CheckoutFlowManager object.
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
    parent::__construct('Plugin/Commerce/CheckoutFlow', $namespaces, $module_handler, 'Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface', 'Drupal\commerce_checkout\Annotation\CommerceCheckoutFlow');

    $this->alterInfo('commerce_checkout_flow_info');
    $this->setCacheBackend($cache_backend, 'commerce_checkout_flow_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    foreach (['id', 'label'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new PluginException(sprintf('The checkout flow %s must define the %s property.', $plugin_id, $required_property));
      }
    }
  }

}
