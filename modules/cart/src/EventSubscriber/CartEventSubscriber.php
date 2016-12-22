<?php

namespace Drupal\commerce_cart\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;

/**
 * Reacts to order events.
 */
class CartEventSubscriber implements EventSubscriberInterface {

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * Constructs a new CartEventSubscriber object.
   *
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   */
  public function __construct(CartProviderInterface $cart_provider) {
    $this->cartProvider = $cart_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = ['commerce_order.place.pre_transition' => 'finalizeCart'];
    return $events;
  }

  /**
   * Finalizes cart.
   *
   * Reacts to order "place" transition event.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The workflow transition event.
   */
  public function finalizeCart(WorkflowTransitionEvent $event) {
    $order = $event->getEntity();
    if ($order->cart->value == TRUE) {
      $this->cartProvider->finalizeCart($order, FALSE);
    }
  }

}
