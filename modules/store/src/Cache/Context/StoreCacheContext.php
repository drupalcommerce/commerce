<?php

namespace Drupal\commerce_store\Cache\Context;

use Drupal\commerce_store\StoreContextInterface;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Defines the StoreCacheContext service, for "per store" caching.
 *
 * Cache context ID: 'store'.
 */
class StoreCacheContext implements CacheContextInterface {

  /**
   * The store context.
   *
   * @var \Drupal\commerce_store\StoreContextInterface
   */
  protected $storeContext;

  /**
   * Constructs a new StoreCacheContext class.
   *
   * @param \Drupal\commerce_store\StoreContextInterface $context
   *   The store context.
   */
  public function __construct(StoreContextInterface $context) {
    $this->storeContext = $context;
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
    return $this->storeContext->getStore()->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
