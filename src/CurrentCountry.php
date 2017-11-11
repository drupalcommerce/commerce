<?php

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
class CurrentCountry implements CurrentCountryInterface {

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
   * Constructs a new CurrentCountry object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\commerce\Resolver\ChainCountryResolverInterface $chain_resolver
   *   The chain resolver.
   */
  public function __construct(RequestStack $request_stack, ChainCountryResolverInterface $chain_resolver) {
    $this->requestStack = $request_stack;
    $this->chainResolver = $chain_resolver;
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
