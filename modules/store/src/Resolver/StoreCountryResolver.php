<?php

namespace Drupal\commerce_store\Resolver;

use Drupal\commerce\Country;
use Drupal\commerce\Resolver\CountryResolverInterface;
use Drupal\commerce_store\CurrentStoreInterface;

/**
 * Returns the store's billing country.
 *
 * A precise default country is important for currency formatting,
 * and the store's billing country is usually more precise than the
 * site's default country returned in DefaultCountryResolver.
 *
 * Note that this resolver sets the convention of the current store
 * being resolved before the current country. Custom resolvers can
 * reverse that convention if needed.
 */
class StoreCountryResolver implements CountryResolverInterface {

  /**
   * The current store.
   *
   * @var \Drupal\commerce_store\CurrentStoreInterface
   */
  protected $currentStore;

  /**
   * Constructs a new StoreCountryResolver object.
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
  public function resolve() {
    $store = $this->currentStore->getStore();
    $address = $store ? $store->getAddress() : NULL;
    if ($address) {
      return new Country($address->getCountryCode());
    }
  }

}
