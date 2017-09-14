<?php

namespace Drupal\commerce_store\Plugin\views\argument_default;

use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default argument plugin for the current store.
 *
 * Note: The plugin ID is 'active_store' instead of 'current_store' for
 *       backwards-compatibility reasons.
 *
 * @ViewsArgumentDefault(
 *   id = "active_store",
 *   title = @Translation("Store ID from the current store")
 * )
 */
class CurrentStore extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * The current store.
   *
   * @var \Drupal\commerce_store\CurrentStoreInterface
   */
  protected $currentStore;

  /**
   * Constructs a new CurrentStore object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   *   The current store.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentStoreInterface $current_store) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->currentStore = $current_store;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce_store.current_store')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    return $this->currentStore->getStore()->id();
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
    return $this->currentStore->getStore()->getCacheTags();
  }

}
