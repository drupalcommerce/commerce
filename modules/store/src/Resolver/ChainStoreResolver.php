<?php

/**
 * @file
 * Contains \Drupal\commerce_store\Resolver\ChainStoreResolver.
 */

namespace Drupal\commerce_store\Resolver;

/**
 * Default implementation of the chain store resolver.
 */
class ChainStoreResolver implements ChainStoreResolverInterface {

  /**
   * The resolvers.
   *
   * @var \Drupal\commerce_store\Resolver\StoreResolverInterface[]
   */
  protected $resolvers = [];

  /**
   * Constructs a new ChainStoreResolver object.
   *
   * @param \Drupal\commerce_store\Resolver\StoreResolverInterface[] $resolvers
   *   The resolvers.
   */
  public function __construct(array $resolvers = []) {
    $this->resolvers = $resolvers;
  }

  /**
   * {@inheritdoc}
   */
  public function addResolver(StoreResolverInterface $resolver) {
    $this->resolvers[] = $resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function getResolvers() {
    return $this->resolvers;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve() {
    foreach ($this->resolvers as $resolver) {
      $result = $resolver->resolve();
      if ($result) {
        return $result;
      }
    }
  }

}
