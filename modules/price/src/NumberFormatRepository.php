<?php

/**
 * @file
 * Contains \Drupal\commerce_price\NumberFormatRepository.
 */

namespace Drupal\commerce_price;

use CommerceGuys\Intl\NumberFormat\NumberFormatRepositoryInterface;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository as ExternalNumberFormatRepository;
use Drupal\commerce_price\Event\NumberFormatEvent;
use Drupal\commerce_price\Event\PriceEvents;
use Drupal\Core\Cache\CacheBackendInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Creates a NumberFormatRepository instance.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   */
  public function __construct(CacheBackendInterface $cache, EventDispatcherInterface $eventDispatcher) {
    $this->cache = $cache;
    $this->eventDispatcher = $eventDispatcher;

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
    // Instantiate and alter the number format.
    $numberFormat = $this->createNumberFormatFromDefinition($definition, $locale);
    $event = new NumberFormatEvent($numberFormat);
    $this->eventDispatcher->dispatch(PriceEvents::NUMBER_FORMAT_LOAD, $event);
    $this->numberFormats[$locale] = $numberFormat;

    return $this->numberFormats[$locale];
  }

}
