<?php

namespace Drupal\commerce_payment;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages discovery and instantiation of payment type plugins.
 *
 * @see \Drupal\commerce_payment\Annotation\CommercePaymentType
 * @see plugin_api
 */
class PaymentTypeManager extends DefaultPluginManager {

  /**
   * Constructs a new PaymentTypeManager object.
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
    parent::__construct('Plugin/Commerce/PaymentType', $namespaces, $module_handler, 'Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeInterface', 'Drupal\commerce_payment\Annotation\CommercePaymentType');

    $this->alterInfo('commerce_payment_type_info');
    $this->setCacheBackend($cache_backend, 'commerce_payment_type_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    foreach (['id', 'label'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new PluginException(sprintf('The payment type %s must define the %s property.', $plugin_id, $required_property));
      }
    }
  }

}
