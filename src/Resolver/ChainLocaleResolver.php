<?php

/**
 * @file
 * Contains \Drupal\commerce\Resolver\ChainLocaleResolver.
 */

namespace Drupal\commerce\Resolver;

/**
 * Default implementation of the chain locale resolver.
 */
class ChainLocaleResolver implements ChainLocaleResolverInterface {

  /**
   * The resolvers.
   *
   * @var \Drupal\commerce\Resolver\LocaleResolverInterface[]
   */
  protected $resolvers = [];

  /**
   * Constructs a new ChainLocaleResolver object.
   *
   * @param \Drupal\commerce\Resolver\LocaleResolverInterface[] $resolvers
   *   The resolvers.
   */
  public function __construct(array $resolvers = []) {
    $this->resolvers = $resolvers;
  }

  /**
   * {@inheritdoc}
   */
  public function addResolver(LocaleResolverInterface $resolver) {
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
