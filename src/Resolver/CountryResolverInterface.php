<?php

/**
 * @file
 * Contains \Drupal\commerce\Resolver\CountryResolverInterface.
 */

namespace Drupal\commerce\Resolver;

/**
 * Defines the interface for country resolvers.
 */
interface CountryResolverInterface {

  /**
   * Resolves the country.
   *
   * @return \Drupal\commerce\Country|null
   *   The country object, if resolved. Otherwise NULL, indicating that the next
   *   resolver in the chain should be called.
   */
  public function resolve();

}
