<?php

/**
 * @file
 * Contains \Drupal\commerce\Country.
 */

namespace Drupal\commerce;

/**
 * Represents a country.
 */
final class Country {

  /**
   * Two-letter country code.
   *
   * @var string
   */
  protected $countryCode;

  /**
   * Constructs a new Country object.
   *
   * @param string $countryCode
   *   The country code.
   */
  public function __construct($countryCode) {
    $this->countryCode = strtoupper($countryCode);
  }

  /**
   * Gets the country code.
   *
   * @return string
   */
  public function getCountryCode() {
    return $this->countryCode;
  }

  /**
   * Gets the string representation of the country.
   *
   * @return string
   */
  public function __toString() {
    return $this->countryCode;
  }

}
