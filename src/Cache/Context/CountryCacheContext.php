<?php

/**
 * @file
 * Contains \Drupal\commerce\Cache\Context\CountryCacheContext
 */

namespace Drupal\commerce\Cache\Context;

use Drupal\commerce\CountryContext;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;

/**
 * Defines the country cache context, for "per country" caching.
 *
 * Cache context ID: 'country'.
 */
class CountryCacheContext implements CacheContextInterface {

  /**
   * The country context.
   *
   * @var \Drupal\commerce\CountryContext
   */
  protected $countryContext;

  /**
   * Constructs a new CountryCacheContext object.
   *
   * @param \Drupal\commerce\CountryContext $context
   *   The country context.
   */
  public function __construct(CountryContext $context) {
    $this->countryContext = $context;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Country');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->countryContext->getCountry()->getCountryCode();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
