<?php

namespace Drupal\commerce\Resolver;

/**
 * Runs the added resolvers one by one until one of them returns the locale.
 *
 * Each resolver in the chain can be another chain, which is why this interface
 * extends the locale resolver one.
 */
interface ChainLocaleResolverInterface extends LocaleResolverInterface {

  /**
   * Adds a resolver.
   *
   * @param \Drupal\commerce\Resolver\LocaleResolverInterface $resolver
   *   The resolver.
   * @param int $priority
   *   The priority.
   */
  public function addResolver(LocaleResolverInterface $resolver, $priority);

  /**
   * Gets all added resolvers.
   *
   * @return \Drupal\commerce\Resolver\LocaleResolverInterface[]
   *   The resolvers.
   */
  public function getResolvers();

}
