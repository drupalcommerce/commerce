<?php

namespace Drupal\commerce_order\Resolver;

use Drupal\commerce_order\Entity\OrderItemInterface;

/**
 * Default implementation of the chain order type resolver.
 */
class ChainOrderTypeResolver implements ChainOrderTypeResolverInterface {

  /**
   * The resolvers.
   *
   * @var \Drupal\commerce_order\Resolver\OrderTypeResolverInterface[]
   */
  protected $resolvers = [];

  /**
   * Constructs a new ChainOrderTypeResolver object.
   *
   * @param \Drupal\commerce_order\Resolver\OrderTypeResolverInterface[] $resolvers
   *   The resolvers.
   */
  public function __construct(array $resolvers = []) {
    $this->resolvers = $resolvers;
  }

  /**
   * {@inheritdoc}
   */
  public function addResolver(OrderTypeResolverInterface $resolver) {
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
  public function resolve(OrderItemInterface $order_item) {
    foreach ($this->resolvers as $resolver) {
      $result = $resolver->resolve($order_item);
      if ($result) {
        return $result;
      }
    }
  }

}
