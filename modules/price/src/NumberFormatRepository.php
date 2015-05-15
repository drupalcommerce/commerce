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
   * Loads the number format definitions for the provided locale.
   *
   * @param string $locale The desired locale.
   *
   * @return array
   */
  protected function loadDefinitions($locale) {
    if (isset($this->definitions[$locale])) {
      return $this->definitions[$locale];
    }

    $cacheKey = 'commerce_price.number_format.' . $locale;
    if ($cached = $this->cache->get($cacheKey)) {
      $this->definitions[$locale] = $cached->data;
    }
    else {
      $filename = $this->definitionPath . $locale . '.json';
      $this->definitions[$locale] = json_decode(file_get_contents($filename), true);
      // Merge-in base definitions.
      $baseDefinitions = $this->loadBaseDefinitions();
      foreach ($this->definitions[$locale] as $numberFormat => $definition) {
        $this->definitions[$locale][$numberFormat] += $this->baseDefinitions[$numberFormat];
      }
      $this->cache->set($cacheKey, $this->definitions[$locale], CacheBackendInterface::CACHE_PERMANENT, ['number_formats']);
    }

    return $this->definitions[$locale];
  }

}
