<?php

/**
 * @file
 * Contains \Drupal\commerce_store\Resolver\StoreResolverInterface.
 */

namespace Drupal\commerce_store\Resolver;

/**
 * Store resolver interface
 *
 * Each resolver tries to determine the active store based on its own logic,
 * and returns it if successful. Otherwise, it returns NULL to indicate that
 * the next resolver in the chain should be called.
 */
interface StoreResolverInterface {

  /**
   * Resolves the store.
   *
   * @return \Drupal\commerce_store\StoreInterface|NULL
   */
  public function resolve();

}
