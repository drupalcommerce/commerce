<?php

/**
 * @file
 * Contains \Drupal\commerce_store\StoreContext.
 */

namespace Drupal\commerce_store;

use Drupal\commerce_store\Resolver\ChainStoreResolverInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Holds a reference to the active store, resolved on demand.
 *
 * The ChainStoreResolver runs the registered store resolvers one by one until
 * one of them returns the store.
 * The DefaultStoreResolver runs last, and will select the default store.
 * Custom resolvers can choose based on the url, the user's country, etc.
 *
 * Note that this functionality is optional, since not every site will be
 * limited to having only one active store at the time.
 *
 * @see \Drupal\commerce_store\Resolver\ChainStoreResolver
 * @see \Drupal\commerce_store\Resolver\DefaultStoreResolver
 */
class StoreContext implements StoreContextInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The chain resolver.
   *
   * @var \Drupal\commerce_store\Resolver\ChainStoreResolverInterface
   */
  protected $chainResolver;

  /**
   * Static cache of resolved stores. One per request.
   *
   * @var \SplObjectStorage
   */
  protected $stores;

  /**
   * Constructs a new StoreContext object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\commerce_store\Resolver\ChainStoreResolverInterface $chain_resolver
   *   The chain resolver.
   */
  public function __construct(RequestStack $request_stack, ChainStoreResolverInterface $chain_resolver) {
    $this->requestStack = $request_stack;
    $this->chainResolver = $chain_resolver;
    $this->stores = new \SplObjectStorage();
  }

  /**
   * {@inheritdoc}
   */
  public function getStore() {
    $request = $this->requestStack->getCurrentRequest();
    if (!$this->stores->contains($request)) {
      $this->stores[$request] = $this->chainResolver->resolve();
    }

    return $this->stores[$request];
  }

}
