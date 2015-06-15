<?php

/**
 * @file
 * Contains \Drupal\commerce\CountryContextInterface.
 */

namespace Drupal\commerce;

/**
 * Country context interface
 *
 * Holds a reference to the current country, resolved on demand.
 *
 * @see \Drupal\commerce\CountryContext
 */
interface CountryContextInterface {

  /**
   * Gets the country for the current request.
   *
   * @return \Drupal\commerce\CountryInterface
   */
  public function getCountry();

}
