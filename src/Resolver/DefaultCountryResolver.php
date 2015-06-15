<?php

/**
 * @file
 * Contains \Drupal\commerce\Resolver\DefaultCountryResolver.
 */

namespace Drupal\commerce\Resolver;

use Drupal\Core\Config\ConfigFactoryInterface;

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
    return $this->configFactory->get('system.date')->get('country.default');
  }

}
