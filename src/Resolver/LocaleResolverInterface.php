<?php

/**
 * @file
 * Contains \Drupal\commerce\Resolver\LocaleResolverInterface.
 */

namespace Drupal\commerce\Resolver;

/**
 * Defines the interface for locale resolvers.
 */
interface LocaleResolverInterface {

  /**
   * Resolves the locale.
   *
   * @return string|null
   *   The locale, if resolved. Otherwise NULL, indicating that the next
   *   resolver in the chain should be called.
   */
  public function resolve();

}
