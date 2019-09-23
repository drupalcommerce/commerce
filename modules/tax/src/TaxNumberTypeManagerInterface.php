<?php

namespace Drupal\commerce_tax;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Defines the interface for tax number type plugin managers.
 */
interface TaxNumberTypeManagerInterface extends PluginManagerInterface, FallbackPluginManagerInterface {

  /**
   * Gets the plugin ID for the given country code.
   *
   * If no country-specific plugin exists, the fallback plugin ID ("other")
   * will be returned.
   *
   * @param string $country_code
   *   The country code.
   *
   * @return string
   *   The plugin ID.
   */
  public function getPluginId($country_code);

}
