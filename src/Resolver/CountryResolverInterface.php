<?php

/**
 * @file
 * Contains \Drupal\commerce\Resolver\CountryResolverInterface.
 */

namespace Drupal\commerce\Resolver;

/**
 * Country resolver interface
 *
 * Each resolver tries to determine the current country based on its own logic,
 * and returns it if successful. Otherwise, it returns NULL to indicate that
 * the next resolver in the chain should be called.
 */
interface CountryResolverInterface {

  /**
   * Resolves the country.
   *
   * @return string|null
   */
  public function resolve();

}
