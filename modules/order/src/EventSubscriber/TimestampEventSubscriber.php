<?php

namespace Drupal\commerce_order\EventSubscriber;

use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TimestampEventSubscriber implements EventSubscriberInterface {

  /**
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new TimestampEventSubscriber object.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   */
  public function __construct(TimeInterface $time) {
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'commerce_order.place.pre_transition' => 'onPlaceTransition',
      'commerce_order.pre_transition' => 'onAnyTransition',
    ];
    return $events;
  }

  /**
   * Sets the order's placed timestamp.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function onPlaceTransition(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    if (empty($order->getPlacedTime())) {
      $order->setPlacedTime($this->time->getRequestTime());
    }
  }

  /**
   * Sets the order's completed timestamp.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function onAnyTransition(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    $to_state_id = $event->getTransition()->getToState()->getId();
    if ($to_state_id == 'completed' && empty($order->getCompletedTime())) {
      $order->setCompletedTime($this->time->getRequestTime());
    }
  }

}
