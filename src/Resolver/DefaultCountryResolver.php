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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve() {
    $countryCode = $this->configFactory->get('system.date')->get('country.default');
    return new Country($countryCode);
  }

}
