<?php

namespace Drupal\commerce_checkout\Resolver;

use Drupal\commerce_checkout\Entity\CheckoutFlowInterface;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Default implementation of the chain checkout flow resolver.
 */
class ChainCheckoutFlowResolver implements ChainCheckoutFlowResolverInterface {

  /**
   * The resolvers.
   *
   * @var \Drupal\commerce_checkout\Resolver\CheckoutFlowResolverInterface[]
   */
  protected $resolvers = [];

  /**
   * Constructs a new ChainCheckoutFlowResolver object.
   *
   * @param \Drupal\commerce_checkout\Resolver\CheckoutFlowResolverInterface[] $resolvers
   *   The resolvers.
   */
  public function __construct(array $resolvers = []) {
    $this->resolvers = $resolvers;
  }

  /**
   * {@inheritdoc}
   */
  public function addResolver(CheckoutFlowResolverInterface $resolver) {
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
  public function resolve(OrderInterface $order) {
    foreach ($this->resolvers as $resolver) {
      $result = $resolver->resolve($order);
      if ($result instanceof CheckoutFlowInterface) {
        return $result;
      }
    }

    return NULL;
  }

}
