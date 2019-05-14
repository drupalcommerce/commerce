<?php

namespace Drupal\commerce_order;

use Drupal\commerce_order\Event\OrderAssignEvent;
use Drupal\commerce_order\Event\OrderEvents;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class OrderAssignment implements OrderAssignmentInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new OrderAssignment object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher) {
    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function assign(OrderInterface $order, UserInterface $customer) {
    // Notify other modules before the order is modified, so that
    // subscribers have access to the original data.
    $event = new OrderAssignEvent($order, $customer);
    $this->eventDispatcher->dispatch(OrderEvents::ORDER_ASSIGN, $event);

    $order->setCustomer($customer);
    $order->setEmail($customer->getEmail());
    $order->save();
  }

  /**
   * {@inheritdoc}
   */
  public function assignMultiple(array $orders, UserInterface $customer) {
    foreach ($orders as $order) {
      $this->assign($order, $customer);
    }
  }

}
