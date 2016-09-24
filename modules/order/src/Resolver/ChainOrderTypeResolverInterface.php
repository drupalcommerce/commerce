<?php

namespace Drupal\commerce_order\Resolver;

/**
 * Runs the added resolvers one by one until one of them returns the order type.
 *
 * Each resolver in the chain can be another chain, which is why this interface
 * extends the order type resolver one.
 */
interface ChainOrderTypeResolverInterface extends OrderTypeResolverInterface {

  /**
   * Adds a resolver.
   *
   * @param \Drupal\commerce_order\Resolver\OrderTypeResolverInterface $resolver
   *   The resolver.
   * @param int $priority
   *   The priority.
   */
  public function addResolver(OrderTypeResolverInterface $resolver, $priority);

  /**
   * Gets all added resolvers.
   *
   * @return \Drupal\commerce_order\Resolver\OrderTypeResolverInterface[]
   *   The resolvers.
   */
  public function getResolvers();

}
