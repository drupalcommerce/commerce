<?php

namespace Drupal\commerce_log\EventSubscriber;

use Drupal\commerce_order\Event\OrderAssignEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderEventSubscriber implements EventSubscriberInterface {

  /**
   * The log storage.
   *
   * @var \Drupal\commerce_log\LogStorageInterface
   */
  protected $logStorage;

  /**
   * Constructs a new OrderEventSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->logStorage = $entity_type_manager->getStorage('commerce_log');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'commerce_order.place.post_transition' => ['onPlaceTransition'],
      'commerce_order.validate.post_transition' => ['onValidateTransition'],
      'commerce_order.fulfill.post_transition' => ['onFulfillTransition'],
      'commerce_order.cancel.post_transition' => ['onCancelTransition'],
      'commerce_order.order.assign' => ['onOrderAssign', -100],
    ];
    return $events;
  }

  /**
   * Creates a log when an order is placed.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function onPlaceTransition(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    $this->logStorage->generate($order, 'order_placed')->save();
  }

  /**
   * Creates a log when an order is validated.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function onValidateTransition(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    $this->logStorage->generate($order, 'order_validated')->save();
  }

  /**
   * Creates a log when an order is fulfilled.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function onFulfillTransition(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    $this->logStorage->generate($order, 'order_fulfilled')->save();
  }

  /**
   * Creates a log when an order is canceled.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function onCancelTransition(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    $this->logStorage->generate($order, 'order_canceled')->save();
  }

  /**
   * Creates a log when an order is assigned.
   *
   * @param \Drupal\commerce_order\Event\OrderAssignEvent $event
   *   The order assign event.
   */
  public function onOrderAssign(OrderAssignEvent $event) {
    $order = $event->getOrder();
    $this->logStorage->generate($order, 'order_assigned', [
      'user' => $event->getCustomer()->getDisplayName(),
    ])->save();
  }

}
