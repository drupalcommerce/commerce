<?php

/**
 * @file
 * Contains \Drupal\commerce_store\Resolver\ChainStoreResolverInterface.
 */

namespace Drupal\commerce_store\Resolver;

/**
 * Runs the added resolvers one by one until one of them returns the store.
 *
 * Each resolver in the chain can be another chain, which is why this interface
 * extends the store resolver one.
 */
interface ChainStoreResolverInterface extends StoreResolverInterface {

  /**
   * Adds a resolver.
   *
   * @param \Drupal\commerce_store\Resolver\StoreResolverInterface $resolver
   *   The resolver.
   */
  public function addResolver(StoreResolverInterface $resolver);

  /**
   * Gets all added resolvers.
   *
   * @return \Drupal\commerce_store\Resolver\StoreResolverInterface[]
   *   The resolvers.
   */
  public function getResolvers();

}
