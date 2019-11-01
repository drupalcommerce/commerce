<?php

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
   * @param string $country_code
   *   The country code.
   */
  public function __construct(string $country_code) {
    $this->countryCode = strtoupper($country_code);
  }

  /**
   * Gets the country code.
   *
   * @return string
   *   The country code.
   */
  public function getCountryCode() : string {
    return $this->countryCode;
  }

  /**
   * Gets the string representation of the country.
   *
   * @return string
   *   The string representation of the country
   */
  public function __toString() : string {
    return $this->countryCode;
  }

}
