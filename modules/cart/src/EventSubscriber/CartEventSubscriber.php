<?php

namespace Drupal\commerce_cart\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;

/**
 * Event reactions for commerce_cart.
 */
class CartEventSubscriber implements EventSubscriberInterface {

  /**
   * @inheritdoc
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
   */
  public function finalizeCart(WorkflowTransitionEvent $event) {
    $order = $event->getEntity();
    \Drupal::service('commerce_cart.cart_provider')->finalizeCart($order, FALSE);
  }

}
