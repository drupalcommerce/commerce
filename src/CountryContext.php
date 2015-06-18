<?php

/**
 * @file
 * Contains \Drupal\commerce\CountryContext.
 */

namespace Drupal\commerce;

use Drupal\commerce\Resolver\ChainCountryResolverInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Holds a reference to the current country, resolved on demand.
 *
 * The ChainCountryResolver runs the registered country resolvers one by one
 * until one of them returns the country.
 * The DefaultCountryResolver runs last, and will select the site's default
 * country. Custom resolvers can choose based on the user profile, GeoIP, etc.
 *
 * @see \Drupal\commerce\Resolver\ChainCountryResolver
 * @see \Drupal\commerce\Resolver\DefaultCountryResolver
 */
class CountryContext implements CountryContextInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The chain resolver.
   *
   * @var \Drupal\commerce\Resolver\ChainCountryResolverInterface
   */
  protected $chainResolver;

  /**
   * Static cache of resolved countries. One per request.
   *
   * @var \SplObjectStorage
   */
  protected $countries;

  /**
   * Constructs a new CountryContext object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\commerce\Resolver\ChainCountryResolverInterface $chainResolver
   *   The chain resolver.
   */
  public function __construct(RequestStack $requestStack, ChainCountryResolverInterface $chainResolver) {
    $this->requestStack = $requestStack;
    $this->chainResolver = $chainResolver;
    $this->countries = new \SplObjectStorage();
  }

  /**
   * {@inheritdoc}
   */
  public function getCountry() {
    $request = $this->requestStack->getCurrentRequest();
    if (!$this->countries->contains($request)) {
      $this->countries[$request] = $this->chainResolver->resolve();
    }

    return $this->countries[$request];
  }

}
