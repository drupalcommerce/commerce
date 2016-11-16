<?php

namespace Drupal\commerce;

/**
 * Holds a reference to the current locale, resolved on demand.
 *
 * @see \Drupal\commerce\CurrentLocale
 */
interface CurrentLocaleInterface {

  /**
   * Gets the locale for the current request.
   *
   * @return \Drupal\commerce\Locale
   *   The locale.
   */
  public function getLocale();

}
