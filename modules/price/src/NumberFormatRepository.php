<?php

/**
 * @file
 * Contains \Drupal\commerce_price\NumberFormatRepository.
 */

namespace Drupal\commerce_price;

use CommerceGuys\Intl\NumberFormat\NumberFormatRepositoryInterface;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository as ExternalNumberFormatRepository;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Defines the number format repository.
 *
 * Number formats are stored on disk in JSON and cached inside Drupal.
 */
class NumberFormatRepository extends ExternalNumberFormatRepository implements NumberFormatRepositoryInterface {

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Creates a NumberFormatRepository instance.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(CacheBackendInterface $cache) {
    $this->cache = $cache;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public function get($locale, $fallbackLocale = null) {
    $locale = $this->resolveLocale($locale, $fallbackLocale);
    if (isset($this->numberFormats[$locale])) {
      return $this->numberFormats[$locale];
    }

    // Load the definition.
    $cacheKey = 'commerce_price.number_format.' . $locale;
    if ($cached = $this->cache->get($cacheKey)) {
      $definition = $cached->data;
    }
    else {
      $filename = $this->definitionPath . $locale . '.json';
      $definition = json_decode(file_get_contents($filename), true);
      $this->cache->set($cacheKey, $definition, CacheBackendInterface::CACHE_PERMANENT, ['number_formats']);
    }
    // Instantiate the number format and add it to the static cache.
    $this->numberFormats[$locale] = $this->createNumberFormatFromDefinition($definition, $locale);

    return $this->numberFormats[$locale];
  }

}
