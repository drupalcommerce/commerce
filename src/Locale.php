<?php

/**
 * @file
 * Contains \Drupal\commerce\Locale.
 */

namespace Drupal\commerce;

/**
 * Represents a locale.
 */
final class Locale {

  /**
   * The locale
   *
   * @var string
   */
  protected $localeCode;

  /**
   * Constructs a new Locale object.
   *
   * @param string $localeCode
   *   The locale code.
   */
  public function __construct($localeCode) {
    $this->localeCode = $localeCode;
  }

  /**
   * Gets the locale code.
   *
   * @return string
   */
  public function getLocaleCode() {
    return $this->localeCode;
  }

  /**
   * Gets the string representation of the locale.
   *
   * @return string
   */
  public function __toString() {
    return $this->localeCode;
  }

}
