<?php

namespace Drupal\commerce_checkout\Resolver;

use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Defines the interface for checkout flow resolvers.
 */
interface CheckoutFlowResolverInterface {

  /**
   * Resolves the checkout flow.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order that is being checked out.
   *
   * @return \Drupal\commerce_checkout\Entity\CheckoutFlowInterface
   *   The checkout flow, if resolved. Otherwise NULL, indicating that
   *   the next resolver in the chain should be called.
   */
  public function resolve(OrderInterface $order);

}
