<?php

namespace Drupal\commerce_checkout\Resolver;

/**
 * Runs the added resolvers one by one until one of them returns the checkout flow.
 *
 * Each resolver in the chain can be another chain, which is why this interface
 * extends the checkout flow resolver one.
 */
interface ChainCheckoutFlowResolverInterface extends CheckoutFlowResolverInterface {

  /**
   * Adds a resolver.
   *
   * @param \Drupal\commerce_checkout\Resolver\CheckoutFlowResolverInterface $resolver
   *   The resolver.
   */
  public function addResolver(CheckoutFlowResolverInterface $resolver);

  /**
   * Gets all added resolvers.
   *
   * @return \Drupal\commerce_checkout\Resolver\CheckoutFlowResolverInterface[]
   *   The resolvers.
   */
  public function getResolvers();

}
