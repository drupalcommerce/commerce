<?php

namespace Drupal\commerce_order\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TimestampEventSubscriber implements EventSubscriberInterface {

  /**
   * The system time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new TimestampEventSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The system time.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TimeInterface $time) {
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'commerce_order.place.pre_transition' => 'onPlaceTransition',
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

}
