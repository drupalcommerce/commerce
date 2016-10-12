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
  public function assign(OrderInterface $order, UserInterface $account) {
    if (!empty($order->getCustomerId())) {
      // Skip orders which already have a customer.
      return;
    }

    $order->setCustomer($account);
    $order->setEmail($account->getEmail());
    // Update the referenced billing profile.
    $billing_profile = $order->getBillingProfile();
    if ($billing_profile && empty($billing_profile->getOwnerId())) {
      $billing_profile->setOwner($account);
      $billing_profile->save();
    }
    // Notify other modules.
    $event = new OrderAssignEvent($order, $account);
    $this->eventDispatcher->dispatch(OrderEvents::ORDER_ASSIGN, $event);

    $order->save();
  }

  /**
   * {@inheritdoc}
   */
  public function assignMultiple(array $orders, UserInterface $account) {
    foreach ($orders as $order) {
      $this->assign($order, $account);
    }
  }

}
