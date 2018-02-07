<?php

namespace Drupal\commerce_order\EventSubscriber;

use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Generates the order number for placed orders.
 *
 * Modules wishing to provide their own order number logic should register
 * an event subscriber with a higher priority (for example, 0).
 *
 * Modules that need access to the generated order number should register
 * an event subscriber with a lower priority (for example, -50).
 */
class OrderNumberSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'commerce_order.place.pre_transition' => ['setOrderNumber', -30],
    ];
    return $events;
  }

  /**
   * Sets the order number to the order ID.
   *
   * Skipped if the order number has already been set.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function setOrderNumber(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    if (!$order->getOrderNumber()) {
      $order->setOrderNumber($order->id());
    }
  }

}
