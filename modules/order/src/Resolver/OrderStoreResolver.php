<?php

namespace Drupal\commerce_order\Resolver;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_store\Resolver\StoreResolverInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Returns the order's store, when an order is present in the URL.
 *
 * Ensures that the current store is always correct when viewing
 * the order in admin pages, or going through checkout.
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
