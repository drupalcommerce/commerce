<?php

/**
 * @file
 * Contains \Drupal\commerce\Resolver\LocaleResolverInterface.
 */

namespace Drupal\commerce\Resolver;

/**
 * Locale resolver interface
 *
 * Each resolver tries to determine the current locale based on its own logic,
 * and returns it if successful. Otherwise, it returns NULL to indicate that
 * the next resolver in the chain should be called.
 */
interface LocaleResolverInterface {

  /**
   * Resolves the locale.
   *
   * @return string|null
   */
  public function resolve();

}
