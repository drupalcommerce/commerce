<?php

namespace Drupal\commerce_order\EventSubscriber;

use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Generates the order number for placed orders.
 *
 * Modules wishing to override this logic can register their
 * own event subscriber with a higher weight (e.g. -10).
 */
class OrderNumberSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'commerce_order.place.pre_transition' => ['setOrderNumber', -100],
    ];
    return $events;
  }

  /**
   * Sets the order number, if not already set explicitly, to the order ID.
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
