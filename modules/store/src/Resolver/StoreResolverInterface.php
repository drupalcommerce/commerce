<?php

/**
 * @file
 * Contains \Drupal\commerce_store\Resolver\StoreResolverInterface.
 */

namespace Drupal\commerce_store\Resolver;

/**
 * Defines the interface for store resolvers.
 */
interface StoreResolverInterface {

  /**
   * Resolves the store.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface|NULL
   *   The store, if resolved. Otherwise NULL, indicating that the next
   *   resolver in the chain should be called.
   */
  public function resolve();

}
