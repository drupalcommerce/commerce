<?php

namespace Drupal\commerce_store\Plugin\views\argument_default;

use Drupal\commerce_store\StoreContextInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default argument plugin for the active store.
 *
 * @ViewsArgumentDefault(
 *   id = "active_store",
 *   title = @Translation("Store ID from active store")
 * )
 */
class ActiveStore extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * The store context.
   *
   * @var \Drupal\commerce_store\StoreContextInterface
   */
  protected $storeContext;

  /**
   * Constructs a PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_store\StoreContextInterface $store_context
   *   The store context.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StoreContextInterface $store_context) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->storeContext = $store_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce_store.store_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    return $this->storeContext->getStore()->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['store'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->storeContext->getStore()->getCacheTags();
  }

}
