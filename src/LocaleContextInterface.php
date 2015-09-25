<?php

/**
 * @file
 * Contains \Drupal\commerce\LocaleContextInterface.
 */

namespace Drupal\commerce;

/**
 * Holds a reference to the current locale, resolved on demand.
 *
 * @see \Drupal\commerce\LocaleContext
 */
interface LocaleContextInterface {

  /**
   * Gets the locale for the current request.
   *
   * @return \Drupal\commerce\Locale
   */
  public function getLocale();

}
