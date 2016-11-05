<?php

namespace Drupal\commerce;

/**
 * Holds a reference to the current country, resolved on demand.
 *
 * @see \Drupal\commerce\CurrentCountry
 */
interface CurrentCountryInterface {

  /**
   * Gets the country for the current request.
   *
   * @return \Drupal\commerce\Country
   *   The country.
   */
  public function getCountry();

}
