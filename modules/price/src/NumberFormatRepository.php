<?php

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
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(CacheBackendInterface $cache, EventDispatcherInterface $event_dispatcher) {
    $this->cache = $cache;
    $this->eventDispatcher = $event_dispatcher;

    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public function get($locale, $fallback_locale = NULL) {
    $locale = $this->resolveLocale($locale, $fallback_locale);
    if (isset($this->numberFormats[$locale])) {
      return $this->numberFormats[$locale];
    }

    // Load the definition.
    $cache_key = 'commerce_price.number_format.' . $locale;
    if ($cached = $this->cache->get($cache_key)) {
      $definition = $cached->data;
    }
    else {
      $filename = $this->definitionPath . $locale . '.json';
      $definition = json_decode(file_get_contents($filename), TRUE);
      $this->cache->set($cache_key, $definition, CacheBackendInterface::CACHE_PERMANENT, ['number_formats']);
    }
    // Instantiate and alter the number format.
    $number_format = $this->createNumberFormatFromDefinition($definition, $locale);
    $event = new NumberFormatEvent($number_format);
    $this->eventDispatcher->dispatch(PriceEvents::NUMBER_FORMAT_LOAD, $event);
    $this->numberFormats[$locale] = $number_format;

    return $this->numberFormats[$locale];
  }

}
