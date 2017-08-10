<?php

namespace Drupal\commerce_store\Cache\Context;

use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Defines the StoreCacheContext service, for "per store" caching.
 *
 * Cache context ID: 'store'.
 */
class StoreCacheContext implements CacheContextInterface {

  /**
   * The current store.
   *
   * @var \Drupal\commerce_store\CurrentStoreInterface
   */
  protected $currentStore;

  /**
   * Constructs a new StoreCacheContext class.
   *
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   *   The current store.
   */
  public function __construct(CurrentStoreInterface $current_store) {
    $this->currentStore = $current_store;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Store');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->currentStore->getStore()->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
