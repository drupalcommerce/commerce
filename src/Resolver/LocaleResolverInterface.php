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
   * @return \Drupal\commerce\Locale|null
   *   The locale object, if resolved. Otherwise NULL, indicating that the next
   *   resolver in the chain should be called.
   */
  public function resolve();

}
