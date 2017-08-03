<?php

namespace Drupal\commerce_store\Resolver;

use Drupal\commerce\Country;
use Drupal\commerce\Resolver\CountryResolverInterface;
use Drupal\commerce_store\StoreContextInterface;

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
   * The store context.
   *
   * @var \Drupal\commerce_store\StoreContextInterface
   */
  protected $storeContext;

  /**
   * Constructs a new StoreCountryResolver object.
   *
   * @param \Drupal\commerce_store\StoreContextInterface $store_context
   *   The store context.
   */
  public function __construct(StoreContextInterface $store_context) {
    $this->storeContext = $store_context;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve() {
    $store = $this->storeContext->getStore();
    if ($store) {
      return new Country($store->getAddress()->getCountryCode());
    }
  }

}
