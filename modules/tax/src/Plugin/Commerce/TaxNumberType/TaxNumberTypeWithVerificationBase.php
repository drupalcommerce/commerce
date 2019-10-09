<?php

namespace Drupal\commerce_tax\Plugin\Commerce\TaxNumberType;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the base class for tax number types which support verification.
 */
abstract class TaxNumberTypeWithVerificationBase extends TaxNumberTypeBase implements ContainerFactoryPluginInterface, SupportsVerificationInterface {

  /**
   * The memory cache.
   *
   * @var \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface
   */
  protected $memoryCache;

  /**
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new TaxNumberTypeWithVerificationBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $memory_cache
   *   The memory cache.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MemoryCacheInterface $memory_cache, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->memoryCache = $memory_cache;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.memory_cache'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function verify($tax_number) {
    // The verification result is cached in memory for the duration of the
    // request, to account for verify() being called multiple times during
    // one form submission (validate -> submit).
    $cache_id = 'verification:' . $this->pluginId . ':' . $tax_number;
    $cache = $this->memoryCache->get($cache_id);
    if ($cache) {
      $result = $cache->data;
    }
    else {
      $result = $this->doVerify($tax_number);
      $this->memoryCache->set($cache_id, $result);
    }

    return $result;
  }

  /**
   * Performs the tax number verification.
   *
   * @param string $tax_number
   *   The tax number.
   *
   * @return \Drupal\commerce_tax\Plugin\Commerce\TaxNumberType\VerificationResult
   *   The verification result.
   */
  abstract protected function doVerify($tax_number);

}
