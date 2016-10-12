<?php

namespace Drupal\commerce_checkout;

use Drupal\commerce_checkout\Resolver\ChainCheckoutFlowResolverInterface;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Manages checkout flows for orders.
 */
class CheckoutOrderManager implements CheckoutOrderManagerInterface {

  /**
   * The chain checkout flow resolver.
   *
   * @var \Drupal\commerce_checkout\Resolver\ChainCheckoutFlowResolverInterface
   */
  protected $chainCheckoutFlowResolver;

  /**
   * Constructs a new CheckoutOrderManager object.
   *
   * @param \Drupal\commerce_checkout\Resolver\ChainCheckoutFlowResolverInterface $chain_checkout_flow_resolver
   *   The chain checkout flow resolver.
   */
  public function __construct(ChainCheckoutFlowResolverInterface $chain_checkout_flow_resolver) {
    $this->chainCheckoutFlowResolver = $chain_checkout_flow_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function getCheckoutFlow(OrderInterface $order) {
    if ($order->checkout_flow->isEmpty()) {
      $checkout_flow = $this->chainCheckoutFlowResolver->resolve($order);
      $order->checkout_flow->target_id = $checkout_flow;
      $order->save();
    }

    return $order->checkout_flow->entity;
  }

}
