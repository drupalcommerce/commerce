<?php

/**
 * @file
 * Contains \Drupal\commerce\Resolver\DefaultCountryResolver.
 */

namespace Drupal\commerce\Resolver;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\commerce\Country;

/**
 * Returns the site's default country.
 */
class DefaultCountryResolver implements CountryResolverInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new DefaultCountryResolver object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve() {
    $country_code = $this->configFactory->get('system.date')->get('country.default');
    return new Country($country_code);
  }

}
