<?php

namespace Drupal\commerce_order\Resolver;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_store\Resolver\StoreResolverInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Returns the a store from an order if present in route parameters.
 */
class OrderStoreResolver implements StoreResolverInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new OrderStoreResolver object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve() {
    $order = $this->routeMatch->getParameter('commerce_order');
    if ($order instanceof OrderInterface) {
      return $order->getStore();
    }
    return NULL;
  }

}
