<?php

namespace Drupal\commerce_tax\Resolver;

/**
 * Runs the added resolvers one by one until one of them returns the tax rate.
 *
 * Each resolver in the chain can be another chain, which is why this interface
 * extends the tax rate resolver one.
 */
interface ChainTaxRateResolverInterface extends TaxRateResolverInterface {

  /**
   * Adds a resolver.
   *
   * @param \Drupal\commerce_tax\Resolver\TaxRateResolverInterface $resolver
   *   The resolver.
   */
  public function addResolver(TaxRateResolverInterface $resolver);

  /**
   * Gets all added resolvers.
   *
   * @return \Drupal\commerce_tax\Resolver\TaxRateResolverInterface[]
   *   The resolvers.
   */
  public function getResolvers();

}
